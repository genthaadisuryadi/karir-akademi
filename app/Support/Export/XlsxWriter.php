<?php

namespace App\Support\Export;

/**
 * Penulis file Excel (.xlsx) satu sheet tanpa dependensi tambahan.
 * Teks disimpan sebagai string biasa (bukan rumus) sehingga aman dari formula injection,
 * dan nomor HP tetap mempertahankan angka 0 di depan.
 */
class XlsxWriter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<list<string|int|float|null>>  $rows
     * @param  list<int|float>  $widths  lebar kolom (karakter), opsional
     */
    public static function build(string $sheetName, array $headers, iterable $rows, array $widths = []): string
    {
        $cols = count($headers);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';

        if ($widths !== []) {
            $xml .= '<cols>';
            foreach ($widths as $i => $w) {
                $xml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $xml .= self::row(1, $headers, 1);

        $r = 1;
        foreach ($rows as $row) {
            $xml .= self::row(++$r, array_values($row), 2);
        }

        $xml .= '</sheetData>'
            .'<autoFilter ref="A1:'.self::col($cols - 1).max(1, $r).'"/>'
            .'</worksheet>';

        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="'.self::esc(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheetName), 0, 31)).'" sheetId="1" r:id="rId1"/></sheets>'
                .'</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
                .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
                .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
                .'<fill><patternFill patternType="solid"><fgColor rgb="FF0352A1"/><bgColor indexed="64"/></patternFill></fill></fills>'
                .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                .'<cellXfs count="3">'
                .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>'
                .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
                .'</cellXfs>'
                .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                .'</styleSheet>',
            'xl/worksheets/sheet1.xml' => $xml,
        ]);
    }

    /** @param list<string|int|float|null> $cells */
    private static function row(int $n, array $cells, int $style): string
    {
        $out = '<row r="'.$n.'">';
        foreach ($cells as $i => $v) {
            $ref = self::col($i).$n;
            if ($v === null || $v === '') {
                $out .= '<c r="'.$ref.'" s="'.$style.'"/>';
            } elseif (is_int($v) || is_float($v)) {
                $out .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$v.'</v></c>';
            } else {
                $out .= '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $v).'</t></is></c>';
            }
        }

        return $out.'</row>';
    }

    /** 0 => A, 25 => Z, 26 => AA */
    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26).$s;
        }

        return $s;
    }

    private static function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string,string> $files nama => isi */
    private static function zip(array $files): string
    {
        $time = (int) date('H') << 11 | (int) date('i') << 5 | intdiv((int) date('s'), 2);
        $date = ((int) date('Y') - 1980) << 9 | (int) date('n') << 5 | (int) date('j');

        $body = '';
        $central = '';
        $count = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $method = 0;
            $data = $content;

            if (function_exists('gzdeflate') && ($deflated = gzdeflate($content, 6)) !== false && strlen($deflated) < $size) {
                $method = 8;
                $data = $deflated;
            }

            $csize = strlen($data);
            $nameLen = strlen($name);
            $offset = strlen($body);

            $body .= pack('VvvvvvVVVvv', 0x04034B50, 20, 0x0800, $method, $time, $date, $crc, $csize, $size, $nameLen, 0).$name.$data;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0x0800, $method, $time, $date, $crc, $csize, $size, $nameLen, 0, 0, 0, 0, 0, $offset).$name;
            $count++;
        }

        return $body.$central.pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, strlen($central), strlen($body), 0);
    }
}
