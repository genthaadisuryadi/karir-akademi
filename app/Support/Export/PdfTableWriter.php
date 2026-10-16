<?php

namespace App\Support\Export;

/**
 * Penulis PDF tabel sederhana (A4 landscape) tanpa dependensi tambahan.
 * Memakai font bawaan PDF (Helvetica), teks otomatis dibungkus per kolom,
 * header tabel diulang di setiap halaman, dan nomor halaman di footer.
 */
class PdfTableWriter
{
    private const PAGE_W = 841.89;

    private const PAGE_H = 595.28;

    private const MARGIN = 30.0;

    private const FOOTER_H = 26.0;

    private const FONT_SIZE = 7.5;

    private const LEADING = 9.5;

    private const PAD = 3.5;

    private const MAX_LINES = 14;

    /** Lebar huruf Helvetica per 1000 unit, kode CP1252 32..255. */
    public const W_REGULAR = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584, 761,
        556, 556, 222, 556, 333, 1000, 556, 556, 333, 1000, 667, 333, 1000, 556, 611, 556,
        556, 222, 222, 333, 333, 350, 556, 1000, 333, 1000, 500, 333, 944, 556, 500, 667,
        278, 333, 556, 556, 556, 556, 260, 556, 333, 737, 370, 556, 584, 333, 737, 333,
        400, 584, 333, 333, 333, 556, 537, 278, 333, 333, 365, 556, 834, 834, 834, 611,
        667, 667, 667, 667, 667, 667, 1000, 722, 667, 667, 667, 667, 278, 278, 278, 278,
        722, 722, 778, 778, 778, 778, 778, 584, 778, 722, 722, 722, 722, 667, 667, 611,
        556, 556, 556, 556, 556, 556, 889, 500, 556, 556, 556, 556, 278, 278, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 584, 611, 556, 556, 556, 556, 500, 556, 500,
    ];

    /** Lebar huruf Helvetica-Bold per 1000 unit, kode CP1252 32..255. */
    public const W_BOLD = [
        278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611,
        975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556,
        333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611,
        611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584, 761,
        556, 556, 278, 556, 500, 1000, 556, 556, 333, 1000, 667, 333, 1000, 556, 611, 556,
        556, 278, 278, 500, 500, 350, 556, 1000, 333, 1000, 556, 333, 944, 556, 500, 667,
        278, 333, 556, 556, 556, 556, 280, 556, 333, 737, 370, 556, 584, 333, 737, 333,
        400, 584, 333, 333, 333, 611, 556, 278, 333, 333, 365, 556, 834, 834, 834, 611,
        722, 722, 722, 722, 722, 722, 1000, 722, 667, 667, 667, 667, 278, 278, 278, 278,
        722, 722, 778, 778, 778, 778, 778, 584, 778, 722, 722, 722, 722, 667, 667, 611,
        556, 556, 556, 556, 556, 556, 889, 556, 556, 556, 556, 556, 278, 278, 278, 278,
        611, 611, 611, 611, 611, 611, 611, 584, 611, 611, 611, 611, 611, 556, 611, 556,
    ];

    /** @var list<string> isi stream tiap halaman */
    private array $pages = [];

    private string $stream = '';

    private float $y = 0;

    private int $rowIndex = 0;

    /** @var list<float> */
    private array $widths = [];

    /**
     * @param  list<array{0:string,1:float|int}>  $columns  [label, bobot lebar]
     */
    public function __construct(
        private readonly string $title,
        private readonly string $subtitle,
        private readonly array $columns,
    ) {
        $usable = self::PAGE_W - 2 * self::MARGIN;
        $sum = array_sum(array_column($columns, 1)) ?: 1;
        foreach ($columns as $col) {
            $this->widths[] = $usable * $col[1] / $sum;
        }

        $this->newPage(true);
    }

    /** @param list<string> $cells */
    public function addRow(array $cells): void
    {
        $lines = [];
        $maxLines = 1;
        foreach ($this->widths as $i => $w) {
            $wrapped = $this->wrap($this->encode((string) ($cells[$i] ?? '')), $w - 2 * self::PAD, self::W_REGULAR);
            if (count($wrapped) > self::MAX_LINES) {
                $wrapped = array_slice($wrapped, 0, self::MAX_LINES);
                $wrapped[self::MAX_LINES - 1] = rtrim($wrapped[self::MAX_LINES - 1]).'...';
            }
            $lines[$i] = $wrapped;
            $maxLines = max($maxLines, count($wrapped));
        }

        $height = $maxLines * self::LEADING + 2 * self::PAD - 1.5;

        if ($this->y - $height < self::MARGIN + self::FOOTER_H) {
            $this->newPage(false);
        }

        $top = $this->y;

        if ($this->rowIndex % 2 === 1) {
            $this->stream .= sprintf("0.96 0.97 0.99 rg %.2F %.2F %.2F %.2F re f\n", self::MARGIN, $top - $height, self::PAGE_W - 2 * self::MARGIN, $height);
        }

        $x = self::MARGIN;
        foreach ($this->widths as $i => $w) {
            $ty = $top - self::PAD - self::FONT_SIZE + 1;
            foreach ($lines[$i] as $line) {
                $this->text($x + self::PAD, $ty, $line, false, self::FONT_SIZE, '0.12 0.16 0.22');
                $ty -= self::LEADING;
            }
            $x += $w;
        }

        $this->stream .= sprintf("0.88 0.9 0.92 RG 0.4 w %.2F %.2F m %.2F %.2F l S\n", self::MARGIN, $top - $height, self::PAGE_W - self::MARGIN, $top - $height);

        $this->y = $top - $height;
        $this->rowIndex++;
    }

    public function output(): string
    {
        $this->pages[] = $this->stream;
        $total = count($this->pages);

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Title ('.$this->escape($this->encode($this->title)).') /Producer (Karir Akademi) >>';

        $n = 6;
        foreach ($this->pages as $i => $content) {
            $label = $this->encode('Halaman '.($i + 1).' dari '.$total);
            $content .= $this->footer($label);

            $filter = '';
            if (function_exists('gzcompress')) {
                $content = gzcompress($content, 6);
                $filter = ' /Filter /FlateDecode';
            }

            $pageId = $n++;
            $contentId = $n++;
            $kids[] = $pageId.' 0 R';
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_W, self::PAGE_H, $contentId
            );
            $objects[$contentId] = '<< /Length '.strlen($content).$filter." >>\nstream\n".$content."\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$total.' >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 ".($max + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size ".($max + 1).' /Root 1 0 R /Info 5 0 R >>'."\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function newPage(bool $first): void
    {
        if (! $first) {
            $this->pages[] = $this->stream;
        }

        $this->stream = '';
        $this->rowIndex = 0;
        $y = self::PAGE_H - self::MARGIN;

        if ($first) {
            $this->text(self::MARGIN, $y - 12, $this->encode($this->title), true, 15, '0.01 0.32 0.63');
            $this->text(self::MARGIN, $y - 27, $this->encode($this->subtitle), false, 8.5, '0.42 0.45 0.5');
            $y -= 40;
        }

        // Baris header tabel
        $h = self::LEADING + 2 * self::PAD;
        $this->stream .= sprintf("0.01 0.32 0.63 rg %.2F %.2F %.2F %.2F re f\n", self::MARGIN, $y - $h, self::PAGE_W - 2 * self::MARGIN, $h);
        $x = self::MARGIN;
        foreach ($this->columns as $i => $col) {
            $label = $this->wrap($this->encode($col[0]), $this->widths[$i] - 2 * self::PAD, self::W_BOLD)[0] ?? '';
            $this->text($x + self::PAD, $y - self::PAD - self::FONT_SIZE + 0.5, $label, true, self::FONT_SIZE, '1 1 1');
            $x += $this->widths[$i];
        }

        $this->y = $y - $h;
    }

    private function footer(string $pageLabel): string
    {
        $saved = $this->stream;
        $this->stream = '';

        $y = self::MARGIN - 6;
        $this->text(self::MARGIN, $y, $this->encode('Karir Akademi by UCIC - '.$this->title), false, 7, '0.42 0.45 0.5');
        $w = $this->textWidth($pageLabel, self::W_REGULAR, 7);
        $this->text(self::PAGE_W - self::MARGIN - $w, $y, $pageLabel, false, 7, '0.42 0.45 0.5');

        $out = $this->stream;
        $this->stream = $saved;

        return $out;
    }

    private function text(float $x, float $y, string $encoded, bool $bold, float $size, string $rgb): void
    {
        if ($encoded === '') {
            return;
        }

        $this->stream .= sprintf(
            "BT /%s %.2F Tf %s rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $size, $rgb, $x, $y, $this->escape($encoded)
        );
    }

    /** UTF-8 -> Windows-1252 (karakter yang tidak ada diganti '?'). */
    private function encode(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        $s = str_replace("\t", ' ', str_replace(["\r\n", "\r"], "\n", $s));

        return mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    /** @param array<int,int> $table */
    private function textWidth(string $s, array $table, float $size): float
    {
        $w = 0;
        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $code = ord($s[$i]);
            $w += $code >= 32 ? ($table[$code - 32] ?? 556) : 0;
        }

        return $w * $size / 1000;
    }

    /**
     * Bungkus teks agar muat di lebar $max (pt). Kata yang terlalu panjang dipotong per karakter.
     *
     * @param  array<int,int>  $table
     * @return list<string>
     */
    private function wrap(string $text, float $max, array $table): array
    {
        $size = self::FONT_SIZE;
        $out = [];

        foreach (explode("\n", $text) as $para) {
            $words = preg_split('/ +/', trim($para), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if ($words === []) {
                $out[] = '';

                continue;
            }

            $line = '';
            foreach ($words as $word) {
                while ($this->textWidth($word, $table, $size) > $max) {
                    if ($line !== '') {
                        $out[] = $line;
                        $line = '';
                    }
                    $cut = 1;
                    while ($cut < strlen($word) && $this->textWidth(substr($word, 0, $cut + 1), $table, $size) <= $max) {
                        $cut++;
                    }
                    $out[] = substr($word, 0, $cut);
                    $word = substr($word, $cut);
                }

                $try = $line === '' ? $word : $line.' '.$word;
                if ($this->textWidth($try, $table, $size) <= $max) {
                    $line = $try;
                } else {
                    $out[] = $line;
                    $line = $word;
                }
            }
            $out[] = $line;
        }

        return $out;
    }
}
