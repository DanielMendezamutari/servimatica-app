<?php

namespace App\Infrastructure\Services\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Generate and download a streamed Excel (.xlsx) file.
     *
     * @param string $filename
     * @param string $sheetTitle
     * @param array $headers Array of header strings
     * @param array $rows Array of associative or indexed row arrays
     * @param array $formats Optional map of column index (1-based) to format ('currency', 'percentage', 'text', 'number')
     * @return StreamedResponse
     */
    public function download(
        string $filename,
        string $sheetTitle,
        array $headers,
        array $rows,
        array $formats = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        // Write Headers
        $colIndex = 1;
        foreach ($headers as $header) {
            $cell = $sheet->getCell([$colIndex, 1]);
            $cell->setValue($header);
            $colIndex++;
        }

        // Header Styling (Dark slate / primary theme with white bold text)
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastColLetter}1";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Dark slate
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Write Rows
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($row as $value) {
                $cell = $sheet->getCell([$colIndex, $rowIndex]);
                $format = $formats[$colIndex] ?? null;

                if ($format === 'currency') {
                    $cell->setValue(is_numeric($value) ? (float)$value : $value);
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getNumberFormat()
                        ->setFormatCode('"Bs. "#,##0.00');
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($format === 'percentage') {
                    $cell->setValue(is_numeric($value) ? (float)$value : $value);
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getNumberFormat()
                        ->setFormatCode('0.00"%"');
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($format === 'number') {
                    $cell->setValue(is_numeric($value) ? (int)$value : $value);
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $cell->setValueExplicit((string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getStyle([$colIndex, $rowIndex])
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto-size columns
        for ($i = 1; $i <= count($headers); $i++) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colString)->setAutoSize(true);
        }

        // Zebra striping and borders for data cells
        if ($rowIndex > 2) {
            $dataRange = "A2:{$lastColLetter}" . ($rowIndex - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
