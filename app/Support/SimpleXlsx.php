<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis berkas Excel (.xlsx) sederhana tanpa paket tambahan: beberapa sheet, baris judul tebal,
 * angka tetap angka (bisa dijumlah di Excel), lebar kolom bisa diatur.
 * Cukup untuk ekspor timesheet dan daftar task; tidak mendukung rumus atau gaya lain.
 */
class SimpleXlsx
{
    /** @var list<array{name: string, rows: list<list<mixed>>, widths: list<int>, title: ?string}> */
    private array $sheets = [];

    /**
     * @param  list<list<mixed>>  $rows  baris pertama = judul kolom
     * @param  list<int>  $widths  lebar kolom (perkiraan jumlah karakter)
     */
    public function addSheet(string $name, array $rows, array $widths = [], ?string $title = null): self
    {
        $name = mb_substr(preg_replace('/[\[\]\*\/\\\\\?:]/', ' ', $name), 0, 31);
        $this->sheets[] = compact('name', 'rows', 'widths', 'title');

        return $this;
    }

    /** Tulis ke berkas sementara dan kembalikan path-nya. */
    public function save(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas Excel.');
        }

        $sheetEntries = '';
        $rels = '';
        $overrides = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $sheetEntries .= '<sheet name="'.$this->esc($sheet['name']).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
            $rels .= '<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $zip->addFromString("xl/worksheets/sheet{$n}.xml", $this->sheetXml($sheet));
        }
        $styleRel = count($this->sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides.'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetEntries.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels
            .'<Relationship Id="rId'.$styleRel.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        // Gaya: 0 biasa, 1 tebal (judul kolom), 2 tebal besar (judul sheet), 3 angka 2 desimal
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="0.##"/></numFmts>'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="13"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE1F3EE"/></patternFill></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/></cellXfs>'
            .'</styleSheet>');

        $zip->close();

        return $path;
    }

    private function sheetXml(array $sheet): string
    {
        $rowsXml = '';
        $r = 1;

        if ($sheet['title']) {
            $rowsXml .= '<row r="1">'.$this->cell('A1', $sheet['title'], 2).'</row>';
            $r = 3;
        }

        foreach ($sheet['rows'] as $i => $row) {
            $cells = '';
            foreach (array_values($row) as $c => $value) {
                $ref = $this->col($c).$r;
                $style = $i === 0 ? 1 : (is_int($value) || is_float($value) ? 3 : 0);
                $cells .= $this->cell($ref, $value, $style);
            }
            $rowsXml .= '<row r="'.$r.'">'.$cells.'</row>';
            $r++;
        }

        $cols = '';
        foreach ($sheet['widths'] as $c => $w) {
            $cols .= '<col min="'.($c + 1).'" max="'.($c + 1).'" width="'.max(6, (int) $w).'" customWidth="1"/>';
        }

        $headerRow = $sheet['title'] ? 3 : 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .($cols ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>'.$rowsXml.'</sheetData></worksheet>';
    }

    private function cell(string $ref, mixed $value, int $style): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$ref.'" s="'.$style.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'" s="'.$style.'"><v>'.$value.'</v></c>';
        }

        return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->esc((string) $value).'</t></is></c>';
    }

    private function col(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26).$name;
        }

        return $name;
    }

    private function esc(string $text): string
    {
        // Buang karakter kontrol yang tidak sah di XML.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
