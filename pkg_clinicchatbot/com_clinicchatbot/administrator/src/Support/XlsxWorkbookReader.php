<?php

namespace Mads\Component\Clinicchatbot\Administrator\Support;

defined('_JEXEC') or die;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class XlsxWorkbookReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_DOC_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_PKG_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @return array<string, array{headers: array<int, string>, rows: array<int, array<int, string>>}>
     */
    public static function readWorkbook(string $filePath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Kunne ikke åbne XLSX-filen.');
        }

        try {
            $sharedStrings = self::readSharedStrings($zip);
            $sheetMap = self::readSheetMap($zip);

            $result = [];

            foreach ($sheetMap as $sheetName => $sheetPath) {
                $rows = self::readSheetRows($zip, $sheetPath, $sharedStrings);
                $table = self::rowsToTable($rows);

                if ($table !== null) {
                    $result[$sheetName] = $table;
                }
            }

            return $result;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $dom = self::loadXml($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', self::NS_MAIN);

        $values = [];

        foreach ($xpath->query('//s:sst/s:si') ?: [] as $item) {
            $textParts = [];

            foreach ($xpath->query('.//s:t', $item) ?: [] as $textNode) {
                $textParts[] = (string) $textNode->textContent;
            }

            $values[] = self::normalizeText(implode('', $textParts));
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private static function readSheetMap(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');

        if ($workbookXml === false) {
            throw new RuntimeException('XLSX-filen mangler workbook.xml.');
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($relsXml === false) {
            throw new RuntimeException('XLSX-filen mangler workbook.xml.rels.');
        }

        $workbookDom = self::loadXml($workbookXml);
        $workbookXPath = new DOMXPath($workbookDom);
        $workbookXPath->registerNamespace('s', self::NS_MAIN);
        $workbookXPath->registerNamespace('r', self::NS_DOC_REL);

        $relsDom = self::loadXml($relsXml);
        $relsXPath = new DOMXPath($relsDom);
        $relsXPath->registerNamespace('rel', self::NS_PKG_REL);

        $relationshipMap = [];

        foreach ($relsXPath->query('//rel:Relationships/rel:Relationship') ?: [] as $relationshipNode) {
            if (!$relationshipNode instanceof DOMElement) {
                continue;
            }

            $id = trim((string) $relationshipNode->getAttribute('Id'));
            $target = trim((string) $relationshipNode->getAttribute('Target'));

            if ($id === '' || $target === '') {
                continue;
            }

            $normalizedTarget = ltrim(str_replace('\\', '/', $target), '/');

            if (str_starts_with($normalizedTarget, 'xl/')) {
                $relationshipMap[$id] = $normalizedTarget;
            } else {
                $relationshipMap[$id] = 'xl/' . $normalizedTarget;
            }
        }

        $sheetMap = [];

        foreach ($workbookXPath->query('//s:sheets/s:sheet') ?: [] as $sheetNode) {
            if (!$sheetNode instanceof DOMElement) {
                continue;
            }

            $name = trim((string) $sheetNode->getAttribute('name'));
            $relationshipId = trim((string) $sheetNode->getAttributeNS(self::NS_DOC_REL, 'id'));

            if ($name === '' || $relationshipId === '') {
                continue;
            }

            if (!isset($relationshipMap[$relationshipId])) {
                continue;
            }

            $sheetMap[$name] = $relationshipMap[$relationshipId];
        }

        return $sheetMap;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array<int, string>>
     */
    private static function readSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $xml = $zip->getFromName($sheetPath);

        if ($xml === false) {
            throw new RuntimeException('Kunne ikke læse ark: ' . $sheetPath);
        }

        $dom = self::loadXml($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', self::NS_MAIN);

        $rows = [];

        foreach ($xpath->query('//s:sheetData/s:row') ?: [] as $rowNode) {
            $row = [];
            $nextColumnIndex = 0;

            foreach ($xpath->query('./s:c', $rowNode) ?: [] as $cellNode) {
                if (!$cellNode instanceof DOMElement) {
                    continue;
                }

                $cellRef = trim((string) $cellNode->getAttribute('r'));
                $columnIndex = $cellRef !== ''
                    ? self::columnLettersToIndex($cellRef)
                    : $nextColumnIndex;

                while (count($row) < $columnIndex) {
                    $row[] = '';
                }

                $row[] = self::readCellValue($xpath, $cellNode, $sharedStrings);
                $nextColumnIndex = count($row);
            }

            $rows[] = self::trimTrailingEmptyCells($row);
        }

        return $rows;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private static function readCellValue(DOMXPath $xpath, DOMElement $cellNode, array $sharedStrings): string
    {
        $type = trim((string) $cellNode->getAttribute('t'));

        if ($type === 'inlineStr') {
            $parts = [];

            foreach ($xpath->query('.//s:is//s:t', $cellNode) ?: [] as $textNode) {
                $parts[] = (string) $textNode->textContent;
            }

            return self::normalizeText(implode('', $parts));
        }

        $valueNode = $xpath->query('./s:v', $cellNode)->item(0);
        $rawValue = $valueNode ? (string) $valueNode->textContent : '';

        if ($type === 's') {
            $index = (int) $rawValue;
            return self::normalizeText($sharedStrings[$index] ?? '');
        }

        if ($type === 'b') {
            return $rawValue === '1' ? 'TRUE' : 'FALSE';
        }

        return self::normalizeText($rawValue);
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}|null
     */
    private static function rowsToTable(array $rows): ?array
    {
        $nonEmptyRows = array_values(array_filter(
            $rows,
            static fn(array $row): bool => self::rowHasContent($row)
        ));

        if ($nonEmptyRows === []) {
            return null;
        }

        $headerRow = array_shift($nonEmptyRows);

        if (!is_array($headerRow)) {
            return null;
        }

        $headers = array_values(array_map(
            static fn(string $value, int $index): string => $value !== '' ? $value : 'Kolonne ' . ($index + 1),
            $headerRow,
            array_keys($headerRow)
        ));

        if ($headers === []) {
            return null;
        }

        $columnCount = count($headers);
        $dataRows = [];

        foreach ($nonEmptyRows as $row) {
            $normalizedRow = array_slice(array_pad($row, $columnCount, ''), 0, $columnCount);

            if (!self::rowHasContent($normalizedRow)) {
                continue;
            }

            $dataRows[] = $normalizedRow;
        }

        return [
            'headers' => $headers,
            'rows' => $dataRows,
        ];
    }

    private static function loadXml(string $xml): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();
            $loaded = $dom->loadXML($xml);

            if (!$loaded) {
                throw new RuntimeException('Ugyldig XML i XLSX-filen.');
            }

            return $dom;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function normalizeText(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param array<int, string> $row
     * @return array<int, string>
     */
    private static function trimTrailingEmptyCells(array $row): array
    {
        $end = count($row);

        while ($end > 0 && trim($row[$end - 1]) === '') {
            $end--;
        }

        return array_slice($row, 0, $end);
    }

    /**
     * @param array<int, string> $row
     */
    private static function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function columnLettersToIndex(string $cellReference): int
    {
        if (!preg_match('/^[A-Z]+/i', $cellReference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[0]);
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}