<?php
// Excel and CSV Parser Helper (Self-contained, no Composer dependencies)

class SimpleXLSX {
    public $rows = [];
    public $error = null;

    public static function parse($filename) {
        $xlsx = new self();
        if (!class_exists('ZipArchive')) {
            $xlsx->error = "ZipArchive PHP extension is not installed";
            return $xlsx;
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            $xlsx->error = "Cannot open zip file";
            return $xlsx;
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $xml = @simplexml_load_string($stringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $str = '';
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $str .= (string)$r->t;
                            }
                        }
                        $sharedStrings[] = $str;
                    }
                }
            }
        }

        // 2. Read sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml !== false) {
            $xml = @simplexml_load_string($sheetXml);
            if ($xml && isset($xml->sheetData)) {
                $maxCol = 0;
                $tempRows = [];
                foreach ($xml->sheetData->row as $row) {
                    $rowIdx = (int)$row['r'] - 1;
                    $r = [];
                    foreach ($row->c as $c) {
                        $ref = (string)$c['r'];
                        preg_match('/^([A-Z]+)/', $ref, $matches);
                        if (!empty($matches)) {
                            $colStr = $matches[1];
                            $colIdx = self::colLetterToIndex($colStr);
                            
                            $val = '';
                            if (isset($c->v)) {
                                $val = (string)$c->v;
                                if (isset($c['t']) && (string)$c['t'] === 's') {
                                    $val = $sharedStrings[(int)$val] ?? '';
                                }
                            }
                            $r[$colIdx] = $val;
                            if ($colIdx > $maxCol) {
                                $maxCol = $colIdx;
                            }
                        }
                    }
                    $tempRows[$rowIdx] = $r;
                }

                // Normalise columns and sort
                ksort($tempRows);
                foreach ($tempRows as $rowIdx => $r) {
                    for ($i = 0; $i <= $maxCol; $i++) {
                        if (!isset($r[$i])) {
                            $r[$i] = '';
                        }
                    }
                    ksort($r);
                    $xlsx->rows[] = $r;
                }
            }
        }
        $zip->close();
        return $xlsx;
    }

    private static function colLetterToIndex($letter) {
        $idx = 0;
        $len = strlen($letter);
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letter[$i]) - 64);
        }
        return $idx - 1;
    }
}

class ExcelReader {
    /**
     * Parses file (CSV or XLSX) into an array of associative rows matching column names.
     */
    public static function read($filePath, $fileExtension) {
        $rows = [];
        
        if (strtolower($fileExtension) === 'csv') {
            if (($handle = fopen($filePath, "r")) !== false) {
                $headers = fgetcsv($handle, 1000, ",");
                if ($headers) {
                    $headers = array_map('trim', $headers);
                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                        $row = [];
                        foreach ($headers as $index => $header) {
                            $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                        }
                        $rows[] = $row;
                    }
                }
                fclose($handle);
            }
        } else {
            // Treat as XLSX
            $xlsx = SimpleXLSX::parse($filePath);
            if ($xlsx->error) {
                throw new HttpException("Error parsing Excel: " . $xlsx->error, 400);
            }
            
            if (!empty($xlsx->rows)) {
                $headers = array_shift($xlsx->rows);
                $headers = array_map('trim', $headers);
                
                foreach ($xlsx->rows as $r) {
                    $row = [];
                    foreach ($headers as $index => $header) {
                        if (!empty($header)) {
                            $row[$header] = isset($r[$index]) ? trim($r[$index]) : '';
                        }
                    }
                    $rows[] = $row;
                }
            }
        }
        
        return $rows;
    }
}
