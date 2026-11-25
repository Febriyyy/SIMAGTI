<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Pembaca tabel sederhana dari berkas .xlsx (sheet pertama) atau .csv, tanpa paket tambahan.
 * Baris pertama = judul kolom; judul dinormalkan (huruf kecil, spasi jadi garis bawah).
 * Hasil: list baris berupa ['judul' => 'nilai'] beserta nomor barisnya di berkas.
 */
class SheetReader
{
    /** @return list<array{line: int, data: array<string, string>}> */
    public static function read(string $path, string $extension, int $maxRows = 1000): array
    {
        $rows = strtolower($extension) === 'xlsx' ? self::xlsx($path) : self::csv($path);
        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($h) => self::key((string) $h), array_shift($rows));
        $out = [];
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($headers as $col => $key) {
                if ($key !== '') {
                    $data[$key] = trim((string) ($row[$col] ?? ''));
                }
            }
            if (implode('', $data) === '') {
                continue; // baris kosong dilewati
            }
            if (count($out) >= $maxRows) {
                throw new RuntimeException("Berkas melebihi {$maxRows} baris. Bagi menjadi beberapa berkas.");
            }
            $out[] = ['line' => $i + 2, 'data' => $data];
        }

        return $out;
    }

    /** "Kode Prodi" -> "kode_prodi" */
    public static function key(string $header): string
    {
        $h = mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header)));

        return trim(preg_replace('/[^a-z0-9]+/', '_', $h), '_');
    }

    /** @return list<list<string>> */
    private static function csv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('Berkas tidak bisa dibaca.');
        }
        $first = (string) fgets($handle);
        rewind($handle);
        // Excel versi Indonesia menyimpan CSV dengan titik koma
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($v) => (string) $v, $row);
        }
        fclose($handle);

        return $rows;
    }

    /** @return list<list<string>> */
    private static function xlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas Excel tidak bisa dibuka.');
        }

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (self::xml($xml)->si as $si) {
                $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map(fn ($r) => (string) $r->t, iterator_to_array($si->r, false)));
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('Sheet pertama tidak ditemukan.');
        }

        $rows = [];
        foreach (self::xml($sheet)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = self::colIndex((string) $c['r']);
                $type = (string) $c['t'];
                $value = match ($type) {
                    's' => $shared[(int) $c->v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    default => (string) $c->v,
                };
                $cells[$col] = $value;
            }
            if ($cells === []) {
                $rows[] = [];

                continue;
            }
            $line = array_fill(0, max(array_keys($cells)) + 1, '');
            foreach ($cells as $col => $value) {
                $line[$col] = $value;
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new RuntimeException('Isi berkas Excel tidak valid.');
        }

        return $xml;
    }

    /** "C12" -> 2 */
    private static function colIndex(string $ref): int
    {
        $letters = preg_replace('/\d+/', '', $ref);
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }
}
