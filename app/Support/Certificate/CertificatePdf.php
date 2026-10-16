<?php

namespace App\Support\Certificate;

use App\Models\Program;
use App\Models\Registration;
use App\Support\Export\PdfTableWriter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Membuat PDF sertifikat 2 halaman tanpa dependensi tambahan.
 *
 * Halaman 1: template depan + nama, nomor, tanggal, dan QR Code (posisi dari config/certificate.php).
 * Halaman 2: template belakang apa adanya (statis).
 *
 * Template PNG diubah ke JPEG (kualitas tinggi, transparansi diratakan ke putih) memakai GD,
 * lalu hasilnya disimpan di samping berkas aslinya sebagai cache agar permintaan berikutnya cepat.
 */
class CertificatePdf
{
    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public static function render(Registration $registration): string
    {
        $program = $registration->program;

        if (! $program->hasCertificateTemplate()) {
            throw new RuntimeException('Template sertifikat kelas ini belum diunggah.');
        }

        $pageW = (float) config('certificate.page_width', 841.89);
        $front = self::loadTemplate($program->certificate_front);
        $back = self::loadTemplate($program->certificate_back);

        $frontH = $pageW * $front['h'] / $front['w'];
        $backH = $pageW * $back['h'] / $back['w'];

        $frontContent = sprintf("q %.2F 0 0 %.2F 0 0 cm /Im0 Do Q\n", $pageW, $frontH)
            .self::frontOverlay($registration, $pageW, $frontH);
        $backContent = sprintf("q %.2F 0 0 %.2F 0 0 cm /Im0 Do Q\n", $pageW, $backH);

        return self::assemble(
            'Sertifikat '.$registration->name,
            [
                ['image' => $front, 'content' => $frontContent, 'w' => $pageW, 'h' => $frontH],
                ['image' => $back, 'content' => $backContent, 'w' => $pageW, 'h' => $backH],
            ]
        );
    }

    /** Tanggal terbit dalam bahasa Indonesia, mis. 16 Oktober 2026. */
    public static function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('j').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }

    private static function frontOverlay(Registration $registration, float $w, float $h): string
    {
        $layout = config('certificate.layout');
        $date = self::formatDate($registration->certificate_issued_at ?? now());
        $out = '';

        // Nama: diperkecil otomatis bila terlalu panjang agar tidak keluar area.
        $cfg = $layout['name'];
        $name = self::encode($registration->name !== '' ? $registration->name : '-');
        $size = $cfg['size'] * $w;
        $max = $cfg['max_width'] * $w;
        $table = $cfg['bold'] ? PdfTableWriter::W_BOLD : PdfTableWriter::W_REGULAR;
        while ($size > 8 && self::textWidth($name, $table, $size) > $max) {
            $size -= 0.5;
        }
        $out .= self::text($cfg, $w, $h, $name, $size);

        $cfg = $layout['number'];
        $out .= self::text($cfg, $w, $h, self::encode((string) $registration->certificate_number), $cfg['size'] * $w);

        $cfg = $layout['date'];
        $out .= self::text($cfg, $w, $h, self::encode($date), $cfg['size'] * $w);

        return $out.self::qr($registration->certificateQrUrl(), $layout['qr'], $w, $h);
    }

    /** @param array{x:float,y:float,color:string,bold:bool} $cfg */
    private static function text(array $cfg, float $w, float $h, string $encoded, float $size): string
    {
        if ($encoded === '') {
            return '';
        }

        $table = $cfg['bold'] ? PdfTableWriter::W_BOLD : PdfTableWriter::W_REGULAR;
        $x = $cfg['x'] * $w - self::textWidth($encoded, $table, $size) / 2;
        $y = $h - $cfg['y'] * $h;

        return sprintf(
            "BT /%s %.2F Tf %s rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $cfg['bold'] ? 'F2' : 'F1', $size, $cfg['color'], $x, $y, self::escape($encoded)
        );
    }

    /** QR Code vektor di atas bidang putih (quiet zone 2 modul). */
    private static function qr(string $url, array $cfg, float $w, float $h): string
    {
        $matrix = QrCode::matrix($url);
        $n = count($matrix);
        $box = $cfg['size'] * $w;
        $left = $cfg['x'] * $w;
        $top = $h - $cfg['y'] * $h; // tepi atas kotak (koordinat PDF)
        $cell = $box / ($n + 4);

        $out = sprintf("1 1 1 rg %.3F %.3F %.3F %.3F re f\n0 0 0 rg\n", $left, $top - $box, $box, $box);
        foreach ($matrix as $r => $row) {
            // Gabungkan modul gelap berurutan dalam satu baris menjadi satu persegi panjang.
            $c = 0;
            while ($c < $n) {
                if (! $row[$c]) {
                    $c++;

                    continue;
                }
                $start = $c;
                while ($c < $n && $row[$c]) {
                    $c++;
                }
                $out .= sprintf(
                    "%.3F %.3F %.3F %.3F re\n",
                    $left + ($start + 2) * $cell,
                    $top - ($r + 3) * $cell,
                    ($c - $start) * $cell,
                    $cell
                );
            }
        }

        return $out."f\n";
    }

    /**
     * Baca template PNG dari penyimpanan, ubah ke JPEG (cache), dan kembalikan datanya.
     *
     * @return array{data:string,w:int,h:int}
     */
    private static function loadTemplate(string $path): array
    {
        $disk = Storage::disk(Program::CERTIFICATE_DISK);
        $cache = $path.'.jpg';

        if ($disk->exists($cache)) {
            $data = $disk->get($cache);
            $info = @getimagesizefromstring($data);
            if ($info !== false) {
                return ['data' => $data, 'w' => $info[0], 'h' => $info[1]];
            }
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('Ekstensi PHP GD diperlukan untuk membuat PDF sertifikat.');
        }

        $source = @imagecreatefromstring((string) $disk->get($path));
        if ($source === false) {
            throw new RuntimeException('Template sertifikat tidak dapat dibaca: '.$path);
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $flat = imagecreatetruecolor($w, $h);
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $source, 0, 0, 0, 0, $w, $h);
        unset($source);

        ob_start();
        imagejpeg($flat, null, 95);
        $data = (string) ob_get_clean();
        unset($flat);

        $disk->put($cache, $data);

        return ['data' => $data, 'w' => $w, 'h' => $h];
    }

    /**
     * @param  list<array{image:array{data:string,w:int,h:int},content:string,w:float,h:float}>  $pages
     */
    private static function assemble(string $title, array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Title ('.self::escape(self::encode($title)).') /Producer (Karir Akademi) >>';

        $kids = [];
        $n = 6;
        foreach ($pages as $page) {
            $imageId = $n++;
            $contentId = $n++;
            $pageId = $n++;
            $kids[] = $pageId.' 0 R';

            $img = $page['image'];
            $objects[$imageId] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $img['w'], $img['h'], strlen($img['data']), $img['data']
            );

            $content = $page['content'];
            $filter = '';
            if (function_exists('gzcompress')) {
                $content = gzcompress($content, 6);
                $filter = ' /Filter /FlateDecode';
            }
            $objects[$contentId] = '<< /Length '.strlen($content).$filter." >>\nstream\n".$content."\nendstream";

            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Im0 %d 0 R >> >> /Contents %d 0 R >>',
                $page['w'], $page['h'], $imageId, $contentId
            );
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>';

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

        return $pdf."trailer\n<< /Size ".($max + 1).' /Root 1 0 R /Info 5 0 R >>'."\nstartxref\n".$xref."\n%%EOF";
    }

    /** UTF-8 -> Windows-1252 (karakter yang tidak ada diganti '?'). */
    private static function encode(string $s): string
    {
        $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? '';

        return mb_convert_encoding(trim($s), 'Windows-1252', 'UTF-8');
    }

    private static function escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    /** @param array<int,int> $table */
    private static function textWidth(string $s, array $table, float $size): float
    {
        $w = 0;
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $code = ord($s[$i]);
            $w += $code >= 32 ? ($table[$code - 32] ?? 556) : 0;
        }

        return $w * $size / 1000;
    }
}
