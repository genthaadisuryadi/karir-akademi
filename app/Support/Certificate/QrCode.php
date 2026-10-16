<?php

namespace App\Support\Certificate;

use InvalidArgumentException;

/**
 * Generator QR Code sederhana tanpa dependensi tambahan.
 *
 * Mode byte, koreksi kesalahan level M, versi 1-10 (cukup untuk URL sertifikat;
 * versi 10 menampung hingga 213 byte). Hasilnya matriks boolean (true = modul gelap)
 * tanpa quiet zone, sehingga bisa digambar sebagai vektor di PDF.
 */
class QrCode
{
    private const MAX_VERSION = 10;

    /**
     * Level M: [jumlah byte data, byte koreksi per blok, jumlah blok grup 1, data per blok grup 1,
     * jumlah blok grup 2, data per blok grup 2] untuk versi 1..10.
     */
    private const BLOCKS = [
        1 => [16, 10, 1, 16, 0, 0],
        2 => [28, 16, 1, 28, 0, 0],
        3 => [44, 26, 1, 44, 0, 0],
        4 => [64, 18, 2, 32, 0, 0],
        5 => [86, 24, 2, 43, 0, 0],
        6 => [108, 16, 4, 27, 0, 0],
        7 => [124, 18, 4, 31, 0, 0],
        8 => [154, 22, 2, 38, 2, 39],
        9 => [182, 22, 3, 36, 2, 37],
        10 => [216, 26, 4, 43, 1, 44],
    ];

    /** Posisi pola alignment per versi 2..10 (versi 1 tidak punya). */
    private const ALIGNMENT = [
        2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
        7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];

    /** @var list<int> */
    private static array $exp = [];

    /** @var list<int> */
    private static array $log = [];

    /** @return list<list<bool>> */
    public static function matrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $version = self::pickVersion(count($bytes));
        $size = 17 + 4 * $version;

        $codewords = self::interleave(self::dataCodewords($bytes, $version), $version);

        $best = null;
        $bestScore = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $m = self::build($codewords, $version, $size, $mask);
            $score = self::penalty($m, $size);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $m;
            }
        }

        return $best;
    }

    private static function pickVersion(int $length): int
    {
        for ($v = 1; $v <= self::MAX_VERSION; $v++) {
            // 4 bit mode + panjang (8 bit utk versi 1-9, 16 bit utk versi 10) + data
            $bits = 4 + ($v < 10 ? 8 : 16) + 8 * $length;
            if ($bits <= self::BLOCKS[$v][0] * 8) {
                return $v;
            }
        }

        throw new InvalidArgumentException('Teks terlalu panjang untuk QR Code.');
    }

    /**
     * @param  list<int>  $bytes
     * @return list<int>
     */
    private static function dataCodewords(array $bytes, int $version): array
    {
        $capacity = self::BLOCKS[$version][0] * 8;
        $bits = '0100'.str_pad(decbin(count($bytes)), $version < 10 ? 8 : 16, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        $bits .= str_repeat('0', min(4, $capacity - strlen($bits)));
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);

        $words = array_map('bindec', str_split($bits, 8));
        for ($pad = 0; count($words) < self::BLOCKS[$version][0]; $pad++) {
            $words[] = $pad % 2 === 0 ? 0xEC : 0x11;
        }

        return $words;
    }

    /**
     * Bagi ke blok, hitung Reed-Solomon, lalu interleave.
     *
     * @param  list<int>  $data
     * @return list<int>
     */
    private static function interleave(array $data, int $version): array
    {
        [, $ecLen, $n1, $d1, $n2, $d2] = self::BLOCKS[$version];

        $dataBlocks = [];
        $offset = 0;
        foreach ([[$n1, $d1], [$n2, $d2]] as [$count, $len]) {
            for ($i = 0; $i < $count; $i++) {
                $dataBlocks[] = array_slice($data, $offset, $len);
                $offset += $len;
            }
        }

        $ecBlocks = array_map(fn (array $block) => self::reedSolomon($block, $ecLen), $dataBlocks);

        $out = [];
        $maxData = max($d1, $d2);
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $out[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($ecBlocks as $block) {
                $out[] = $block[$i];
            }
        }

        return $out;
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    private static function reedSolomon(array $data, int $ecLen): array
    {
        self::initGalois();

        // Polinomial generator (x - a^0)(x - a^1)...(x - a^(ecLen-1))
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            foreach ($gen as $j => $coef) {
                $next[$j] ^= $coef;
                $next[$j + 1] ^= self::mul($coef, self::$exp[$i]);
            }
            $gen = $next;
        }

        $rem = array_fill(0, $ecLen, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($rem);
            $rem[] = 0;
            if ($factor !== 0) {
                for ($i = 0; $i < $ecLen; $i++) {
                    $rem[$i] ^= self::mul($gen[$i + 1], $factor);
                }
            }
        }

        return $rem;
    }

    private static function initGalois(): void
    {
        if (self::$exp !== []) {
            return;
        }

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
    }

    private static function mul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$exp[(self::$log[$a] + self::$log[$b]) % 255];
    }

    /**
     * @param  list<int>  $codewords
     * @return list<list<bool>>
     */
    private static function build(array $codewords, int $version, int $size, int $mask): array
    {
        $m = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        $set = function (int $r, int $c, bool $dark) use (&$m, &$reserved, $size): void {
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                $m[$r][$c] = $dark;
                $reserved[$r][$c] = true;
            }
        };

        // Finder pattern + separator
        foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$r0, $c0]) {
            for ($r = -1; $r <= 7; $r++) {
                for ($c = -1; $c <= 7; $c++) {
                    $edge = $r === 0 || $r === 6 || $c === 0 || $c === 6;
                    $core = $r >= 2 && $r <= 4 && $c >= 2 && $c <= 4;
                    $inside = $r >= 0 && $r <= 6 && $c >= 0 && $c <= 6;
                    $set($r0 + $r, $c0 + $c, $inside && ($edge || $core));
                }
            }
        }

        // Timing pattern
        for ($i = 8; $i < $size - 8; $i++) {
            $set(6, $i, $i % 2 === 0);
            $set($i, 6, $i % 2 === 0);
        }

        // Alignment pattern
        if ($version >= 2) {
            $pos = self::ALIGNMENT[$version];
            $last = count($pos) - 1;
            foreach ($pos as $i => $r) {
                foreach ($pos as $j => $c) {
                    if (($i === 0 && $j === 0) || ($i === 0 && $j === $last) || ($i === $last && $j === 0)) {
                        continue;
                    }
                    for ($dr = -2; $dr <= 2; $dr++) {
                        for ($dc = -2; $dc <= 2; $dc++) {
                            $set($r + $dr, $c + $dc, max(abs($dr), abs($dc)) !== 1);
                        }
                    }
                }
            }
        }

        // Modul gelap tetap
        $set($size - 8, 8, true);

        // Area informasi format dan versi dipesan dulu (diisi setelah data)
        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }
        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $reserved[$i][$size - 11 + $j] = true;
                    $reserved[$size - 11 + $j][$i] = true;
                }
            }
        }

        // Tempatkan data zig-zag dari kanan bawah
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }
        $total = strlen($bits);
        $idx = 0;
        $up = true;
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            for ($i = 0; $i < $size; $i++) {
                $r = $up ? $size - 1 - $i : $i;
                for ($k = 0; $k < 2; $k++) {
                    $c = $col - $k;
                    if ($reserved[$r][$c]) {
                        continue;
                    }
                    $bit = $idx < $total ? $bits[$idx] === '1' : false;
                    $idx++;
                    if (self::maskApplies($mask, $r, $c)) {
                        $bit = ! $bit;
                    }
                    $m[$r][$c] = $bit;
                }
            }
            $up = ! $up;
        }

        self::placeFormat($m, $size, $mask);
        if ($version >= 7) {
            self::placeVersion($m, $size, $version);
        }

        return $m;
    }

    private static function maskApplies(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
            5 => ($r * $c) % 2 + ($r * $c) % 3 === 0,
            6 => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0,
            default => (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0,
        };
    }

    /** @param list<list<bool>> $m */
    private static function placeFormat(array &$m, int $size, int $mask): void
    {
        // Level koreksi M = 00, lalu nomor mask, ditambah BCH(15,5) dan XOR 0x5412
        $data = (0b00 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        $bit = fn (int $i): bool => (($bits >> $i) & 1) === 1;

        for ($i = 0; $i <= 5; $i++) {
            $m[$i][8] = $bit($i);
        }
        $m[7][8] = $bit(6);
        $m[8][8] = $bit(7);
        $m[8][7] = $bit(8);
        for ($i = 9; $i < 15; $i++) {
            $m[8][14 - $i] = $bit($i);
        }

        for ($i = 0; $i < 8; $i++) {
            $m[8][$size - 1 - $i] = $bit($i);
        }
        for ($i = 8; $i < 15; $i++) {
            $m[$size - 15 + $i][8] = $bit($i);
        }
        $m[$size - 8][8] = true;
    }

    /** @param list<list<bool>> $m */
    private static function placeVersion(array &$m, int $size, int $version): void
    {
        $rem = $version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = ($version << 12) | $rem;

        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) === 1;
            $a = intdiv($i, 3);
            $b = $i % 3 + $size - 11;
            $m[$a][$b] = $dark;
            $m[$b][$a] = $dark;
        }
    }

    /** @param list<list<bool>> $m */
    private static function penalty(array $m, int $size): int
    {
        $score = 0;

        // Aturan 1: deretan 5+ modul sewarna (baris dan kolom)
        for ($pass = 0; $pass < 2; $pass++) {
            for ($i = 0; $i < $size; $i++) {
                $run = 1;
                for ($j = 1; $j < $size; $j++) {
                    $cur = $pass === 0 ? $m[$i][$j] : $m[$j][$i];
                    $prev = $pass === 0 ? $m[$i][$j - 1] : $m[$j - 1][$i];
                    if ($cur === $prev) {
                        $run++;
                        if ($run === 5) {
                            $score += 3;
                        } elseif ($run > 5) {
                            $score++;
                        }
                    } else {
                        $run = 1;
                    }
                }
            }
        }

        // Aturan 2: blok 2x2 sewarna
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                if ($m[$r][$c] === $m[$r][$c + 1] && $m[$r][$c] === $m[$r + 1][$c] && $m[$r][$c] === $m[$r + 1][$c + 1]) {
                    $score += 3;
                }
            }
        }

        // Aturan 3: pola mirip finder (1:1:3:1:1) dengan 4 modul terang di sisi
        $patA = [true, false, true, true, true, false, true, false, false, false, false];
        $patB = array_reverse($patA);
        for ($pass = 0; $pass < 2; $pass++) {
            for ($i = 0; $i < $size; $i++) {
                for ($j = 0; $j <= $size - 11; $j++) {
                    $a = $b = true;
                    for ($k = 0; $k < 11; $k++) {
                        $v = $pass === 0 ? $m[$i][$j + $k] : $m[$j + $k][$i];
                        $a = $a && $v === $patA[$k];
                        $b = $b && $v === $patB[$k];
                    }
                    $score += ($a ? 40 : 0) + ($b ? 40 : 0);
                }
            }
        }

        // Aturan 4: keseimbangan modul gelap
        $dark = 0;
        foreach ($m as $row) {
            $dark += count(array_filter($row));
        }
        $percent = $dark * 100 / ($size * $size);
        $score += (int) (abs($percent - 50) / 5) * 10;

        return $score;
    }
}
