<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Claim\Service;

use DateTime;
use OrangeHRM\Claim\Dao\ClaimDao;
use OrangeHRM\Config\Config;
use OrangeHRM\Entity\ClaimExpense;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;

class ExpenseClaimReportService
{
    use EmployeeServiceTrait;

    public const TEMPLATE_DATA_START_ROW = 10;
    public const TEMPLATE_DEFAULT_LINE_COUNT = 13;
    public const TEMPLATE_TOTALS_ROW = 23;

    public const REPORT_COLUMNS = [
        'mileage',
        'gas',
        'vehicle',
        'wellness',
        'cellular',
        'office',
        'meal',
        'travelling',
        'other',
    ];

    private const COLUMN_LETTER_MAP = [
        'gas' => 'D',
        'vehicle' => 'E',
        'wellness' => 'F',
        'cellular' => 'G',
        'office' => 'H',
        'meal' => 'I',
        'travelling' => 'J',
        'other' => 'K',
    ];

    /**
     * @var ClaimDao|null
     */
    protected ?ClaimDao $claimDao = null;

    /**
     * @return ClaimDao
     */
    public function getClaimDao(): ClaimDao
    {
        return $this->claimDao ??= new ClaimDao();
    }

    /**
     * @param ClaimDao $claimDao
     */
    public function setClaimDao(ClaimDao $claimDao): void
    {
        $this->claimDao = $claimDao;
    }

    /**
     * @param int $empNumber
     * @param int $year
     * @param int $month
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(int $empNumber, int $year, int $month): array
    {
        $expenses = $this->getClaimDao()->getApprovedExpensesForMonth($empNumber, $year, $month);
        $rows = [];
        foreach ($expenses as $expense) {
            $rows[] = $this->mapExpenseToRow($expense);
        }

        $commissionTotal = $this->getClaimDao()->getCommissionSumForMonth($empNumber, $year, $month);
        if ($commissionTotal > 0) {
            $monthEnd = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))
                ->modify('last day of this month');
            $rows[] = [
                'date' => $monthEnd->format('Y-m-d'),
                'km' => null,
                'mileageCost' => null,
                'gas' => null,
                'vehicle' => null,
                'wellness' => null,
                'cellular' => null,
                'office' => null,
                'meal' => null,
                'travelling' => null,
                'other' => $commissionTotal,
                'otherNote' => 'Commission',
            ];
        }

        return $rows;
    }

    /**
     * @param ClaimExpense $expense
     * @return array<string, mixed>
     */
    private function mapExpenseToRow(ClaimExpense $expense): array
    {
        $row = [
            'date' => $expense->getDate()->format('Y-m-d'),
            'km' => null,
            'mileageCost' => null,
            'gas' => null,
            'vehicle' => null,
            'wellness' => null,
            'cellular' => null,
            'office' => null,
            'meal' => null,
            'travelling' => null,
            'other' => null,
            'otherNote' => null,
        ];

        $reportColumn = $expense->getExpenseType()->getReportColumn();
        $amount = $expense->getAmount();

        if ($reportColumn === ExpenseClaimLimitService::REPORT_COLUMN_MILEAGE) {
            $row['km'] = $expense->getQuantityKm() !== null ? (float) $expense->getQuantityKm() : null;
            $row['mileageCost'] = $amount;
        } elseif ($reportColumn === 'other') {
            $row['other'] = $amount;
            $row['otherNote'] = $expense->getNote();
        } elseif ($reportColumn !== null && array_key_exists($reportColumn, $row)) {
            $row[$reportColumn] = $amount;
        } else {
            $row['other'] = $amount;
            $row['otherNote'] = $expense->getNote() ?? $expense->getExpenseType()->getName();
        }

        return $row;
    }

    /**
     * @param int $empNumber
     * @param int $year
     * @param int $month
     * @return string
     */
    public function generateXlsx(int $empNumber, int $year, int $month): string
    {
        $employee = $this->getEmployeeOrFail($empNumber);
        $rows = $this->buildRows($empNumber, $year, $month);
        $spreadsheet = IOFactory::load($this->getTemplatePath());
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue(
            'B4',
            trim($employee->getFirstName() . ' ' . $employee->getLastName())
        );
        $monthEnd = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))
            ->modify('last day of this month');
        $sheet->setCellValue('B5', $monthEnd->format('Y-m-d'));

        $lineCount = count($rows);
        $extraRows = max(0, $lineCount - self::TEMPLATE_DEFAULT_LINE_COUNT);
        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(self::TEMPLATE_TOTALS_ROW, $extraRows);
            for ($i = 0; $i < $extraRows; $i++) {
                $targetRow = self::TEMPLATE_DATA_START_ROW + self::TEMPLATE_DEFAULT_LINE_COUNT + $i;
                $this->copyRowStyle($sheet, self::TEMPLATE_DATA_START_ROW, $targetRow);
            }
        }

        foreach ($rows as $index => $row) {
            $excelRow = self::TEMPLATE_DATA_START_ROW + $index;
            $sheet->setCellValue('A' . $excelRow, $row['date']);
            if ($row['km'] !== null) {
                $sheet->setCellValue('B' . $excelRow, $row['km']);
            }
            if ($row['mileageCost'] !== null) {
                $sheet->setCellValue('C' . $excelRow, $row['mileageCost']);
            }
            foreach (self::COLUMN_LETTER_MAP as $key => $letter) {
                if ($row[$key] !== null) {
                    $sheet->setCellValue($letter . $excelRow, $row[$key]);
                }
            }
            if ($row['otherNote'] !== null) {
                $sheet->setCellValue('L' . $excelRow, $row['otherNote']);
            }
        }

        $totalsRow = self::TEMPLATE_TOTALS_ROW + $extraRows;
        $dataEndRow = $lineCount > 0
            ? self::TEMPLATE_DATA_START_ROW + $lineCount - 1
            : self::TEMPLATE_DATA_START_ROW + self::TEMPLATE_DEFAULT_LINE_COUNT - 1;
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
            $sheet->setCellValue(
                $col . $totalsRow,
                sprintf('=SUM(%s%d:%s%d)', $col, self::TEMPLATE_DATA_START_ROW, $col, $dataEndRow)
            );
        }
        $sheet->setCellValue('L' . $totalsRow, null);

        return $this->spreadsheetToString($spreadsheet);
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $sourceRow
     * @param int $targetRow
     */
    private function copyRowStyle($sheet, int $sourceRow, int $targetRow): void
    {
        foreach (range('A', 'L') as $col) {
            $sheet->duplicateStyle(
                $sheet->getStyle($col . $sourceRow),
                $col . $targetRow
            );
        }
        $sheet->getRowDimension($targetRow)->setRowHeight(
            $sheet->getRowDimension($sourceRow)->getRowHeight()
        );
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @return string
     */
    private function spreadsheetToString(Spreadsheet $spreadsheet): string
    {
        $writer = new Xlsx($spreadsheet);
        $temp = fopen('php://temp', 'r+');
        $writer->save($temp);
        rewind($temp);
        $content = stream_get_contents($temp);
        fclose($temp);
        $spreadsheet->disconnectWorksheets();
        return $content === false ? '' : $content;
    }

    /**
     * @return string
     */
    private function getTemplatePath(): string
    {
        $pluginTemplate = Config::get(Config::PLUGINS_DIR)
            . DIRECTORY_SEPARATOR
            . 'orangehrmClaimPlugin'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'templates'
            . DIRECTORY_SEPARATOR
            . 'expense_claim_form.xlsx';
        if (is_readable($pluginTemplate)) {
            return $pluginTemplate;
        }
        return dirname(__DIR__) . '/config/templates/expense_claim_form.xlsx';
    }

    /**
     * @param int $empNumber
     * @param int $year
     * @param int $month
     * @return string
     */
    public function generateCoverPdf(int $empNumber, int $year, int $month): string
    {
        $employee = $this->getEmployeeOrFail($empNumber);
        $rows = $this->buildRows($empNumber, $year, $month);
        $monthEnd = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))
            ->modify('last day of this month');
        $employeeName = trim($employee->getFirstName() . ' ' . $employee->getLastName());

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('OrangeHRM');
        $pdf->SetAuthor('OrangeHRM');
        $pdf->SetTitle('Expense Claim Form');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'EXPENSE CLAIM FORM', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Employee name:', 0, 0);
        $pdf->Cell(80, 6, $employeeName, 0, 1);
        $pdf->Cell(40, 6, 'Date:', 0, 0);
        $pdf->Cell(80, 6, $monthEnd->format('Y-m-d'), 0, 1);
        $pdf->Ln(3);

        $headers = [
            'Date', 'KM', 'Mileage', 'Gas', 'Vehicle', 'Wellness',
            'Cellular', 'Office', 'Meal', 'Travelling', 'Other', 'Other note',
        ];
        $widths = [22, 14, 18, 18, 18, 20, 18, 18, 18, 22, 18, 40];

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(230, 230, 230);
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 7);
        $totals = array_fill(0, 11, 0.0);
        foreach ($rows as $row) {
            $cells = [
                $row['date'],
                $this->formatOptionalNumber($row['km']),
                $this->formatOptionalNumber($row['mileageCost']),
                $this->formatOptionalNumber($row['gas']),
                $this->formatOptionalNumber($row['vehicle']),
                $this->formatOptionalNumber($row['wellness']),
                $this->formatOptionalNumber($row['cellular']),
                $this->formatOptionalNumber($row['office']),
                $this->formatOptionalNumber($row['meal']),
                $this->formatOptionalNumber($row['travelling']),
                $this->formatOptionalNumber($row['other']),
                (string) ($row['otherNote'] ?? ''),
            ];
            foreach ($cells as $i => $value) {
                $pdf->Cell($widths[$i], 6, $value, 1, 0, $i === 0 || $i === 11 ? 'L' : 'R');
            }
            $pdf->Ln();

            if ($row['km'] !== null) {
                $totals[0] += (float) $row['km'];
            }
            foreach (['mileageCost', 'gas', 'vehicle', 'wellness', 'cellular', 'office', 'meal', 'travelling', 'other'] as $idx => $key) {
                if ($row[$key] !== null) {
                    $totals[$idx + 1] += (float) $row[$key];
                }
            }
        }

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell($widths[0], 6, 'Totals', 1, 0, 'L');
        for ($i = 0; $i < 10; $i++) {
            $pdf->Cell($widths[$i + 1], 6, $totals[$i] > 0 ? number_format($totals[$i], 2, '.', '') : '', 1, 0, 'R');
        }
        $pdf->Cell($widths[11], 6, '', 1, 1, 'L');

        return $pdf->Output('', 'S');
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function formatOptionalNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @param int $empNumber
     * @param int $year
     * @param int $month
     * @return string
     */
    public function generateMonthlyPdf(int $empNumber, int $year, int $month): string
    {
        $tempFiles = [];
        try {
            $coverBinary = $this->generateCoverPdf($empNumber, $year, $month);
            $coverPath = tempnam(sys_get_temp_dir(), 'claim_cover_');
            file_put_contents($coverPath, $coverBinary);
            $tempFiles[] = $coverPath;

            $pdf = new Fpdi('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);

            $pageCount = $pdf->setSourceFile($coverPath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $expenses = $this->getClaimDao()->getApprovedExpensesForMonth($empNumber, $year, $month);
            $requestIds = [];
            foreach ($expenses as $expense) {
                $requestIds[$expense->getClaimRequest()->getId()] = true;
            }
            $requestIds = array_keys($requestIds);
            $attachments = $this->getClaimDao()->getAttachmentsForRequestIds($requestIds);

            $appendix = [];
            foreach ($attachments as $attachment) {
                $fileType = (string) $attachment->getFileType();
                $filename = (string) $attachment->getFilename();
                $content = $attachment->getDecorator()->getAttachment();

                if (stripos($fileType, 'pdf') !== false || str_ends_with(strtolower($filename), '.pdf')) {
                    $this->appendPdfAttachment($pdf, $content, $tempFiles);
                } elseif (stripos($fileType, 'image/') === 0) {
                    $this->appendImageAttachment($pdf, $content, $fileType, $filename, $tempFiles);
                } else {
                    $appendix[] = $filename !== '' ? $filename : ('attachment-' . $attachment->getAttachId());
                }
            }

            if (!empty($appendix)) {
                $pdf->AddPage('L');
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 8, 'Appendix — unsupported attachments', 0, 1);
                $pdf->SetFont('helvetica', '', 10);
                foreach ($appendix as $name) {
                    $pdf->Cell(0, 6, '- ' . $name, 0, 1);
                }
            }

            return $pdf->Output('', 'S');
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (is_string($tempFile) && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    /**
     * @param Fpdi $pdf
     * @param string $content
     * @param array $tempFiles
     */
    private function appendPdfAttachment(Fpdi $pdf, string $content, array &$tempFiles): void
    {
        $path = tempnam(sys_get_temp_dir(), 'claim_att_');
        file_put_contents($path, $content);
        $tempFiles[] = $path;
        try {
            $pageCount = $pdf->setSourceFile($path);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        } catch (\Throwable $e) {
            // Corrupt/unsupported PDF — list in appendix via caller if needed; skip silently here
        }
    }

    /**
     * @param Fpdi $pdf
     * @param string $content
     * @param string $fileType
     * @param string $filename
     * @param array $tempFiles
     */
    private function appendImageAttachment(
        Fpdi $pdf,
        string $content,
        string $fileType,
        string $filename,
        array &$tempFiles
    ): void {
        $ext = 'jpg';
        if (stripos($fileType, 'png') !== false) {
            $ext = 'png';
        } elseif (stripos($fileType, 'gif') !== false) {
            $ext = 'gif';
        }
        $path = tempnam(sys_get_temp_dir(), 'claim_img_') . '.' . $ext;
        file_put_contents($path, $content);
        $tempFiles[] = $path;

        $pdf->AddPage('L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, $filename, 0, 1);
        $pdf->Image($path, 15, 25, 250, 0, strtoupper($ext), '', '', false, 300, '', false, false, 0);
    }

    /**
     * @param Employee $employee
     * @param int $year
     * @param int $month
     * @param string $extension pdf|xlsx
     * @return string
     */
    public function buildDownloadFilename(Employee $employee, int $year, int $month, string $extension): string
    {
        $monthName = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('F');
        $name = preg_replace('/[^A-Za-z0-9]+/', '_', trim($employee->getFirstName() . '_' . $employee->getLastName()));
        $name = trim((string) $name, '_');
        if ($name === '') {
            $name = 'Employee';
        }
        return sprintf('%s_ExpenseReport_%s_%d.%s', $name, $monthName, $year, $extension);
    }

    /**
     * @param int $empNumber
     * @return Employee
     */
    private function getEmployeeOrFail(int $empNumber): Employee
    {
        $employee = $this->getEmployeeService()->getEmployeeDao()->getEmployeeByEmpNumber($empNumber);
        if (!$employee instanceof Employee) {
            throw new \InvalidArgumentException('Employee not found');
        }
        return $employee;
    }
}
