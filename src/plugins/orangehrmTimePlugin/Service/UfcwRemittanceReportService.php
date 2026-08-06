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

namespace OrangeHRM\Time\Service;

use DateTime;
use OrangeHRM\Core\Service\EmailService;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Entity\UfcwInitiationFee;
use OrangeHRM\Time\Dao\UfcwRemittanceDao;
use OrangeHRM\Time\Dto\UfcwRemittanceEmployeeRow;
use OrangeHRM\Time\Dto\UfcwRemittanceSettings;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UfcwRemittanceReportService
{
    use EntityManagerHelperTrait;

    public const DESIGNATION_FULL_TIME = 'Full-time';
    public const DESIGNATION_PART_TIME = 'Part-time';
    public const DESIGNATION_OTHER = 'Other / N/A';

    private ?UfcwRemittanceDao $dao = null;
    private ?UfcwRemittanceSettingsService $settingsService = null;
    private ?EmailService $emailService = null;

    public function getDao(): UfcwRemittanceDao
    {
        return $this->dao ??= new UfcwRemittanceDao();
    }

    public function setDao(UfcwRemittanceDao $dao): void
    {
        $this->dao = $dao;
    }

    public function getSettingsService(): UfcwRemittanceSettingsService
    {
        return $this->settingsService ??= new UfcwRemittanceSettingsService();
    }

    public function setSettingsService(UfcwRemittanceSettingsService $settingsService): void
    {
        $this->settingsService = $settingsService;
    }

    public function getEmailService(): EmailService
    {
        return $this->emailService ??= new EmailService();
    }

    public function setEmailService(EmailService $emailService): void
    {
        $this->emailService = $emailService;
    }

    /**
     * @param DateTime $reportMonth First day of reporting month
     * @param string|null $preparedBy
     * @param array<int, array<string, mixed>> $overrides keyed by empNumber
     * @return array{
     *   reportMonth: string,
     *   reportMonthLabel: string,
     *   remittanceDueDate: string,
     *   preparedBy: string,
     *   datePrepared: string,
     *   payrollPeriods: string,
     *   status: string,
     *   settings: array<string, mixed>,
     *   employees: array<int, array<string, mixed>>,
     *   totals: array{unionDues: float, initiationFees: float, remittance: float},
     *   sheetName: string
     * }
     */
    public function buildReport(DateTime $reportMonth, ?string $preparedBy = null, array $overrides = []): array
    {
        $settings = $this->getSettingsService()->getSettings();
        $monthStart = new DateTime($reportMonth->format('Y-m-01'));
        $monthEnd = (clone $monthStart)->modify('last day of this month');
        $payrollPeriods = $this->getDao()->getPayrollPeriodsOverlappingMonth($monthStart, $monthEnd);
        $periodLabels = $this->formatPayrollPeriodLabels($payrollPeriods);

        $employees = $this->getDao()->getBargainingUnitEmployees(
            $settings->getMembershipName(),
            $monthStart,
            $monthEnd
        );

        $rows = [];
        foreach ($employees as $employee) {
            $row = $this->buildEmployeeRow($employee, $settings, $monthStart, $monthEnd, $payrollPeriods);
            $empNumber = $row->getEmpNumber();
            if (isset($overrides[$empNumber])) {
                $row->applyOverride($overrides[$empNumber]);
            } elseif (isset($overrides[(string) $empNumber])) {
                $row->applyOverride($overrides[(string) $empNumber]);
            }
            $rows[] = $row;
        }

        $totalDues = 0.0;
        $totalInitiation = 0.0;
        foreach ($rows as $row) {
            $totalDues += $row->getUnionDuesDeducted();
            $totalInitiation += $row->getInitiationFeesDeducted();
        }

        $dueDate = $this->calculateRemittanceDueDate($monthStart);
        $prepared = $preparedBy ?: '';
        $datePrepared = (new DateTime('today'))->format('Y-m-d');
        $sheetName = $monthStart->format('F Y') . ' Report';

        $hasBlockingIssues = false;
        foreach ($rows as $row) {
            if ($row->needsNoDeductionReason() || !empty($row->getMissingRequiredFields()) || !empty($row->getReviewFlags())) {
                $hasBlockingIssues = true;
                break;
            }
        }

        return [
            'reportMonth' => $monthStart->format('Y-m-d'),
            'reportMonthLabel' => $monthStart->format('F Y'),
            'remittanceDueDate' => $dueDate->format('Y-m-d'),
            'preparedBy' => $prepared,
            'datePrepared' => $datePrepared,
            'payrollPeriods' => $periodLabels,
            'status' => $hasBlockingIssues
                ? 'Draft – complete highlighted fields'
                : 'Draft – ready for review',
            'settings' => $settings->toArray(),
            'employees' => array_map(static fn (UfcwRemittanceEmployeeRow $r) => $r->toArray(), $rows),
            'totals' => [
                'unionDues' => round($totalDues, 2),
                'initiationFees' => round($totalInitiation, 2),
                'remittance' => round($totalDues + $totalInitiation, 2),
            ],
            'sheetName' => $sheetName,
            'employeeRows' => $rows,
        ];
    }

    /**
     * @param DateTime $reportMonth
     * @param string|null $preparedBy
     * @param array<int, array<string, mixed>> $overrides
     * @return string Binary XLSX content
     */
    public function generateXlsx(DateTime $reportMonth, ?string $preparedBy = null, array $overrides = []): string
    {
        $report = $this->buildReport($reportMonth, $preparedBy, $overrides);
        /** @var UfcwRemittanceEmployeeRow[] $rows */
        $rows = $report['employeeRows'];
        $settings = $this->getSettingsService()->getSettings();

        $spreadsheet = new Spreadsheet();
        $detail = $spreadsheet->getActiveSheet();
        $detail->setTitle($this->sanitizeSheetTitle($report['sheetName']));
        $this->buildDetailSheet($detail, $report, $rows, $settings);

        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Remittance Summary');
        $this->buildSummarySheet($summary, $report, $settings);

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $this->buildInstructionsSheet($instructions, $settings);

        $spreadsheet->setActiveSheetIndex(0);
        return $this->spreadsheetToString($spreadsheet);
    }

    /**
     * @param DateTime $reportMonth
     * @param string|null $preparedBy
     * @param array<int, array<string, mixed>> $overrides
     * @param bool $updateInitiationBalances
     * @return array{sent: bool, filename: string, recipients: string[]}
     */
    public function emailReport(
        DateTime $reportMonth,
        ?string $preparedBy = null,
        array $overrides = [],
        bool $updateInitiationBalances = false
    ): array {
        $report = $this->buildReport($reportMonth, $preparedBy, $overrides);
        $xlsx = $this->generateXlsx($reportMonth, $preparedBy, $overrides);
        $filename = $this->buildDownloadFilename($reportMonth);
        $settings = $this->getSettingsService()->getSettings();

        $recipients = [];
        if ($settings->getRemittanceEmail() !== '') {
            $recipients[] = $settings->getRemittanceEmail();
        }
        if ($settings->getPayrollEmail() !== '') {
            foreach (preg_split('/[,;]+/', $settings->getPayrollEmail()) ?: [] as $email) {
                $email = trim($email);
                if ($email !== '') {
                    $recipients[] = $email;
                }
            }
        }
        $recipients = array_values(array_unique($recipients));

        $emailService = $this->getEmailService();
        $emailService->setMessageTo($recipients);
        $emailService->setMessageSubject(sprintf(
            'UFCW Monthly Remittance – %s – %s',
            $settings->getEmployerName(),
            $report['reportMonthLabel']
        ));
        $emailService->setMessageBody(
            '<p>Please find attached the UFCW monthly dues &amp; membership remittance report for '
            . htmlspecialchars($report['reportMonthLabel'], ENT_QUOTES)
            . '.</p>'
            . '<p>Regular union dues: $' . number_format($report['totals']['unionDues'], 2)
            . '<br/>Initiation fees: $' . number_format($report['totals']['initiationFees'], 2)
            . '<br/>Total remittance: $' . number_format($report['totals']['remittance'], 2)
            . '<br/>Remittance due: ' . htmlspecialchars($report['remittanceDueDate'], ENT_QUOTES)
            . '</p>'
            . '<p>Cheque payable to: ' . htmlspecialchars($settings->getChequePayableTo(), ENT_QUOTES)
            . '<br/>Attention: ' . htmlspecialchars($settings->getChequeAttention(), ENT_QUOTES)
            . '</p>'
            . '<p>A hard copy of the detailed dues report should accompany the cheque. '
            . 'Do not offset prior overpayments.</p>'
        );
        $emailService->clearAttachments();
        $emailService->addAttachment(
            $xlsx,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $sent = !empty($recipients) && $emailService->sendEmail();

        if ($sent && $updateInitiationBalances) {
            $this->applyInitiationFeePayments($report['employeeRows']);
        }

        return [
            'sent' => $sent,
            'filename' => $filename,
            'recipients' => $recipients,
        ];
    }

    /**
     * @param UfcwRemittanceEmployeeRow[] $rows
     */
    public function applyInitiationFeePayments(array $rows): void
    {
        foreach ($rows as $row) {
            $amount = $row->getInitiationFeesDeducted();
            if ($amount <= 0) {
                continue;
            }
            $employee = $this->getRepository(Employee::class)->find($row->getEmpNumber());
            if (!$employee instanceof Employee) {
                continue;
            }
            $fee = $this->getDao()->getInitiationFee($row->getEmpNumber());
            if ($fee === null) {
                $fee = new UfcwInitiationFee();
                $fee->setEmployee($employee);
                $fee->setFeeRequired(number_format($row->getInitiationFeeRequired(), 2, '.', ''));
                $fee->setAmountPaid('0.00');
            }
            $newPaid = (float) $fee->getAmountPaid() + $amount;
            $fee->setAmountPaid(number_format($newPaid, 2, '.', ''));
            if ((float) $fee->getFeeRequired() <= 0 && $row->getInitiationFeeRequired() > 0) {
                $fee->setFeeRequired(number_format($row->getInitiationFeeRequired(), 2, '.', ''));
            }
            $this->getDao()->saveInitiationFee($fee);
        }
    }

    public function buildDownloadFilename(DateTime $reportMonth): string
    {
        return sprintf('UFCW_Remittance_%s.xlsx', $reportMonth->format('Y-m'));
    }

    public function calculateRemittanceDueDate(DateTime $reportMonth): DateTime
    {
        $year = (int) $reportMonth->format('Y');
        $month = (int) $reportMonth->format('n');
        if ($month === 12) {
            return new DateTime(sprintf('%d-01-15', $year + 1));
        }
        return new DateTime(sprintf('%d-%02d-15', $year, $month + 1));
    }

    /**
     * Weekly dues = (hourlyRate × multiplier) + flatFee
     */
    public function calculateWeeklyDues(float $hourlyRate, UfcwRemittanceSettings $settings): float
    {
        return round(($hourlyRate * $settings->getDuesHourlyMultiplier()) + $settings->getDuesWeeklyFlatFee(), 2);
    }

    /**
     * @param Employee $employee
     * @param UfcwRemittanceSettings $settings
     * @param DateTime $monthStart
     * @param DateTime $monthEnd
     * @param PayrollPeriod[] $payrollPeriods
     * @return UfcwRemittanceEmployeeRow
     */
    private function buildEmployeeRow(
        Employee $employee,
        UfcwRemittanceSettings $settings,
        DateTime $monthStart,
        DateTime $monthEnd,
        array $payrollPeriods
    ): UfcwRemittanceEmployeeRow {
        $row = new UfcwRemittanceEmployeeRow();
        $row->setEmpNumber($employee->getEmpNumber());
        $row->setSin((string) ($employee->getSinNumber() ?? ''));
        $row->setEmployeeId((string) ($employee->getEmployeeId() ?? ''));
        $row->setFullName($this->formatFullName($employee));
        $row->setFullAddress($this->formatAddress($employee));
        $row->setCity((string) ($employee->getCity() ?? ''));
        $row->setProvince((string) ($employee->getProvince() ?? ''));
        $row->setPostalCode((string) ($employee->getZipcode() ?? ''));
        $row->setTelephone($this->resolveTelephone($employee));
        $row->setEmail($this->resolveEmail($employee));
        $joined = $employee->getJoinedDate();
        $row->setDateOfHire($joined ? $joined->format('Y-m-d') : null);

        $jobTitle = $employee->getJobTitle();
        $row->setClassification($jobTitle ? (string) $jobTitle->getJobTitleName() : '');
        $row->setFtPtDesignation($this->resolveFtPtDesignation($employee, $row));

        [$rate, $rateFlags] = $this->resolveHourlyRate($employee);
        $row->setRateOfPay($rate);
        foreach ($rateFlags as $flag) {
            $row->addReviewFlag($flag);
        }

        $weekSummaries = $this->getDao()->getApprovedTimesheetWeekSummaries(
            $employee->getEmpNumber(),
            $monthStart,
            $monthEnd
        );
        $weeksWithHours = 0;
        $weekEndingLabels = [];
        foreach ($weekSummaries as $summary) {
            $durationSeconds = (float) ($summary['duration'] ?? 0);
            if ($durationSeconds <= 0) {
                continue;
            }
            ++$weeksWithHours;
            $endDate = $summary['endDate'];
            if ($endDate instanceof DateTime) {
                $weekEndingLabels[] = $endDate->format('Y-m-d');
            }
        }
        $row->setWeeksWithHours($weeksWithHours);
        $row->setWeekEndingDates(implode(', ', $weekEndingLabels));
        $row->setPayrollPeriods($this->formatPayrollPeriodLabels($payrollPeriods));

        $unionDues = 0.0;
        if ($rate !== null && $weeksWithHours > 0) {
            $unionDues = round($this->calculateWeeklyDues($rate, $settings) * $weeksWithHours, 2);
        }
        $row->setUnionDuesDeducted($unionDues);

        $initiation = $this->calculateInitiationDeduction($employee, $row, $settings, $weeksWithHours);
        $row->setInitiationFeesDeducted($initiation['deducted']);
        $row->setInitiationFeeRequired($initiation['required']);
        $row->setInitiationFeePaidToDate($initiation['paidToDate']);
        $row->setInitiationFeeRemaining($initiation['remainingAfter']);

        if ($weeksWithHours === 0 && $unionDues <= 0 && $initiation['deducted'] <= 0) {
            // Do not invent a reason — leave blank so validation highlights it.
            $row->addReviewFlag('No approved hours in reporting month — confirm reason for no deduction');
        }

        return $row;
    }

    /**
     * @return array{deducted: float, required: float, paidToDate: float, remainingAfter: float}
     */
    private function calculateInitiationDeduction(
        Employee $employee,
        UfcwRemittanceEmployeeRow $row,
        UfcwRemittanceSettings $settings,
        int $weeksWithHours
    ): array {
        $required = $this->resolveRequiredInitiationFee($row->getFtPtDesignation(), $settings);
        $fee = $this->getDao()->getInitiationFee($employee->getEmpNumber());
        $paidToDate = $fee ? (float) $fee->getAmountPaid() : 0.0;
        if ($fee && (float) $fee->getFeeRequired() > 0) {
            $required = (float) $fee->getFeeRequired();
        } elseif ($fee === null && $required > 0) {
            // New member balance — required from designation; paid starts at 0.
        }

        $remaining = max(0.0, $required - $paidToDate);
        $weeklyMax = $this->resolveInitiationWeeklyMax($row->getFtPtDesignation(), $settings);
        $cap = $weeklyMax * max(0, $weeksWithHours);
        $deducted = $remaining > 0 && $weeksWithHours > 0
            ? round(min($remaining, $cap), 2)
            : 0.0;

        return [
            'deducted' => $deducted,
            'required' => $required,
            'paidToDate' => $paidToDate,
            'remainingAfter' => max(0.0, $remaining - $deducted),
        ];
    }

    private function resolveRequiredInitiationFee(string $designation, UfcwRemittanceSettings $settings): float
    {
        if ($designation === self::DESIGNATION_FULL_TIME) {
            return $settings->getInitiationFeeFullTime();
        }
        if ($designation === self::DESIGNATION_PART_TIME) {
            return $settings->getInitiationFeePartTime();
        }
        return 0.0;
    }

    private function resolveInitiationWeeklyMax(string $designation, UfcwRemittanceSettings $settings): float
    {
        if ($designation === self::DESIGNATION_FULL_TIME) {
            return $settings->getInitiationWeeklyMaxFullTime();
        }
        if ($designation === self::DESIGNATION_PART_TIME) {
            return $settings->getInitiationWeeklyMaxPartTime();
        }
        return 0.0;
    }

    /**
     * @return array{0: ?float, 1: string[]}
     */
    private function resolveHourlyRate(Employee $employee): array
    {
        $salaries = $this->getDao()->getEmployeeSalaries($employee->getEmpNumber());
        $flags = [];
        if (count($salaries) === 0) {
            $flags[] = 'Missing rate of pay';
            return [null, $flags];
        }
        if (count($salaries) > 1) {
            $flags[] = 'Multiple salary records — confirm which hourly rate applies';
        }
        $amount = $salaries[0]->getAmount();
        if ($amount === null || $amount === '') {
            $flags[] = 'Missing rate of pay';
            return [null, $flags];
        }
        return [(float) $amount, $flags];
    }

    private function resolveFtPtDesignation(Employee $employee, UfcwRemittanceEmployeeRow $row): string
    {
        $status = $employee->getEmpStatus();
        if ($status === null) {
            $row->addReviewFlag('Missing employment status (FT/PT)');
            return self::DESIGNATION_OTHER;
        }
        $name = strtolower($status->getName());
        if (str_contains($name, 'full')) {
            return self::DESIGNATION_FULL_TIME;
        }
        if (str_contains($name, 'part')) {
            return self::DESIGNATION_PART_TIME;
        }
        $row->addReviewFlag('Employment status not mapped to Full-time/Part-time — review designation');
        return self::DESIGNATION_OTHER;
    }

    private function formatFullName(Employee $employee): string
    {
        $initials = '';
        $middle = trim($employee->getMiddleName());
        if ($middle !== '') {
            $parts = preg_split('/\s+/', $middle) ?: [];
            $letters = [];
            foreach ($parts as $part) {
                $letters[] = strtoupper(substr($part, 0, 1));
            }
            $initials = implode('', $letters);
        }
        $name = $employee->getLastName() . ', ' . $employee->getFirstName();
        if ($initials !== '') {
            $name .= ' ' . $initials;
        }
        return $name;
    }

    private function formatAddress(Employee $employee): string
    {
        $parts = array_filter([
            trim((string) ($employee->getStreet1() ?? '')),
            trim((string) ($employee->getStreet2() ?? '')),
        ], static fn ($v) => $v !== '');
        return implode(', ', $parts);
    }

    private function resolveTelephone(Employee $employee): string
    {
        foreach ([$employee->getMobile(), $employee->getHomeTelephone(), $employee->getWorkTelephone()] as $phone) {
            if ($phone !== null && trim($phone) !== '') {
                return trim($phone);
            }
        }
        return '';
    }

    private function resolveEmail(Employee $employee): string
    {
        foreach ([$employee->getWorkEmail(), $employee->getOtherEmail()] as $email) {
            if ($email !== null && trim($email) !== '') {
                return trim($email);
            }
        }
        return '';
    }

    /**
     * @param PayrollPeriod[] $periods
     */
    private function formatPayrollPeriodLabels(array $periods): string
    {
        $labels = [];
        foreach ($periods as $period) {
            if ($period->getLabel()) {
                $labels[] = $period->getLabel();
            } else {
                $labels[] = 'P' . $period->getPeriodNumber();
            }
        }
        return implode(', ', $labels);
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array<string, mixed> $report
     * @param UfcwRemittanceEmployeeRow[] $rows
     * @param UfcwRemittanceSettings $settings
     */
    private function buildDetailSheet($sheet, array $report, array $rows, UfcwRemittanceSettings $settings): void
    {
        $yellow = 'FFFF99';
        $red = 'FFCCCC';
        $headerFill = 'D9E1F2';

        $sheet->mergeCells('A1:S1');
        $sheet->setCellValue('A1', 'UFCW MONTHLY DUES & MEMBERSHIP REPORT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Employer');
        $sheet->mergeCells('C3:J3');
        $sheet->setCellValue('C3', $settings->getEmployerName());
        $sheet->setCellValue('A4', 'Work Location');
        $sheet->mergeCells('C4:J4');
        $sheet->setCellValue('C4', $settings->getWorkLocation());
        $sheet->setCellValue('A5', 'Work Location Code');
        $sheet->setCellValue('C5', $settings->getWorkLocationCode());
        $sheet->setCellValue('E5', 'Report Month / Year');
        $sheet->setCellValue('G5', DateTime::createFromFormat('Y-m-d', $report['reportMonth']));
        $sheet->getStyle('G5')->getNumberFormat()->setFormatCode('MMMM YYYY');
        $sheet->mergeCells('G5:J5');

        $sheet->setCellValue('A6', 'Union Contact(s)');
        $sheet->mergeCells('C6:J6');
        $sheet->setCellValue('C6', $settings->getUnionContacts());
        $sheet->setCellValue('A7', 'Prepared By');
        $sheet->setCellValue('C7', $report['preparedBy']);
        $sheet->getStyle('C7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($yellow);
        $sheet->setCellValue('E7', 'Date Prepared');
        $sheet->setCellValue('G7', $report['datePrepared']);
        $sheet->mergeCells('G7:J7');
        $sheet->setCellValue('A8', 'Payroll Periods Included');
        $sheet->setCellValue('C8', $report['payrollPeriods']);
        $sheet->setCellValue('E8', 'Report Status');
        $sheet->setCellValue('G8', $report['status']);
        $sheet->mergeCells('G8:J8');

        $sheet->setCellValue('P3', 'Reporting Rules');
        $sheet->getStyle('P3')->getFont()->setBold(true);
        $sheet->setCellValue(
            'P4',
            sprintf(
                'Weekly dues = Hourly rate × %s + $%s',
                rtrim(rtrim(number_format($settings->getDuesHourlyMultiplier(), 4, '.', ''), '0'), '.'),
                number_format($settings->getDuesWeeklyFlatFee(), 2)
            )
        );
        $sheet->setCellValue('P5', 'Remittance deadline');
        $sheet->setCellValue('R5', $report['remittanceDueDate']);
        $sheet->setCellValue('P6', 'Totals: see Remittance Summary sheet');
        $sheet->setCellValue('P7', 'Do not subtract overpayments from the current remittance');
        $sheet->setCellValue('P8', 'Email report to ' . $settings->getRemittanceEmail() . ' and attach hard copy with cheque');

        $sheet->mergeCells('A10:S10');
        $sheet->setCellValue(
            'A10',
            'Yellow cells require completion or confirmation before the report is sent. Amounts in the Total Deducted column calculate automatically.'
        );
        $sheet->getStyle('A10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($yellow);

        $headers = [
            'A' => 'S.I.N.',
            'B' => 'Employee #',
            'C' => 'Full Name',
            'D' => 'Full Address',
            'E' => 'City',
            'F' => 'Prov.',
            'G' => 'Postal Code',
            'H' => 'Telephone',
            'I' => 'Employee Email',
            'J' => 'Date of Hire',
            'K' => 'Rate of Pay',
            'L' => 'Classification',
            'M' => 'FT/PT Designation',
            'N' => 'Payroll Period(s)',
            'O' => 'Week Ending Date(s)',
            'P' => 'Union Dues Deducted',
            'Q' => 'Initiation Fees Deducted',
            'R' => 'Total Deducted',
            'S' => 'Reason No Deduction / Notes',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '11', $label);
        }
        $sheet->getStyle('A11:S11')->getFont()->setBold(true);
        $sheet->getStyle('A11:S11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($headerFill);

        $startRow = 12;
        $rowIndex = $startRow;
        foreach ($rows as $employeeRow) {
            $sheet->setCellValueExplicit('A' . $rowIndex, $employeeRow->getSin(), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $rowIndex, $employeeRow->getEmployeeId(), DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $rowIndex, $employeeRow->getFullName());
            $sheet->setCellValue('D' . $rowIndex, $employeeRow->getFullAddress());
            $sheet->setCellValue('E' . $rowIndex, $employeeRow->getCity());
            $sheet->setCellValue('F' . $rowIndex, $employeeRow->getProvince());
            $sheet->setCellValueExplicit('G' . $rowIndex, $employeeRow->getPostalCode(), DataType::TYPE_STRING);
            $sheet->setCellValue('H' . $rowIndex, $employeeRow->getTelephone());
            $sheet->setCellValue('I' . $rowIndex, $employeeRow->getEmail());
            $sheet->setCellValue('J' . $rowIndex, $employeeRow->getDateOfHire());
            if ($employeeRow->getRateOfPay() !== null) {
                $sheet->setCellValue('K' . $rowIndex, $employeeRow->getRateOfPay());
                $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            }
            $sheet->setCellValue('L' . $rowIndex, $employeeRow->getClassification());
            $sheet->setCellValue('M' . $rowIndex, $employeeRow->getFtPtDesignation());
            $sheet->setCellValue('N' . $rowIndex, $employeeRow->getPayrollPeriods());
            $sheet->setCellValue('O' . $rowIndex, $employeeRow->getWeekEndingDates());
            $sheet->setCellValue('P' . $rowIndex, $employeeRow->getUnionDuesDeducted());
            $sheet->setCellValue('Q' . $rowIndex, $employeeRow->getInitiationFeesDeducted());
            $sheet->setCellValue('R' . $rowIndex, sprintf('=IF(COUNTA(A%d:Q%d)=0,"",SUM(P%d:Q%d))', $rowIndex, $rowIndex, $rowIndex, $rowIndex));
            $notes = trim($employeeRow->getReasonNoDeduction());
            if ($employeeRow->getNotes() !== '') {
                $notes = trim($notes . ($notes !== '' ? ' | ' : '') . $employeeRow->getNotes());
            }
            if (!empty($employeeRow->getReviewFlags())) {
                $notes = trim($notes . ($notes !== '' ? ' | ' : '') . 'REVIEW: ' . implode('; ', $employeeRow->getReviewFlags()));
            }
            $sheet->setCellValue('S' . $rowIndex, $notes);

            $sheet->getStyle('P' . $rowIndex . ':R' . $rowIndex)
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $missing = $employeeRow->getMissingRequiredFields();
            $map = [
                'telephone' => 'H',
                'email' => 'I',
                'rateOfPay' => 'K',
                'classification' => 'L',
                'ftPtDesignation' => 'M',
            ];
            foreach ($missing as $field) {
                if (isset($map[$field])) {
                    $sheet->getStyle($map[$field] . $rowIndex)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($yellow);
                }
            }
            if ($employeeRow->needsNoDeductionReason()) {
                $sheet->getStyle('S' . $rowIndex)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($red);
            }
            ++$rowIndex;
        }

        $totalsRow = $rowIndex;
        $lastEmployeeRow = max($startRow, $totalsRow - 1);
        $sheet->setCellValue('O' . $totalsRow, 'Monthly Totals');
        $sheet->getStyle('O' . $totalsRow)->getFont()->setBold(true);
        if (count($rows) > 0) {
            $sheet->setCellValue('P' . $totalsRow, sprintf('=SUM(P%d:P%d)', $startRow, $lastEmployeeRow));
            $sheet->setCellValue('Q' . $totalsRow, sprintf('=SUM(Q%d:Q%d)', $startRow, $lastEmployeeRow));
            $sheet->setCellValue('R' . $totalsRow, sprintf('=SUM(R%d:R%d)', $startRow, $lastEmployeeRow));
        } else {
            $sheet->setCellValue('P' . $totalsRow, 0);
            $sheet->setCellValue('Q' . $totalsRow, 0);
            $sheet->setCellValue('R' . $totalsRow, 0);
        }
        $sheet->getStyle('P' . $totalsRow . ':R' . $totalsRow)
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->setCellValue('S' . $totalsRow, 'Verify totals against Remittance Summary');

        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array<string, mixed> $report
     * @param UfcwRemittanceSettings $settings
     */
    private function buildSummarySheet($sheet, array $report, UfcwRemittanceSettings $settings): void
    {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'UFCW LOCAL 175 – MONTHLY REMITTANCE SUMMARY');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Employer');
        $sheet->setCellValue('C3', $settings->getEmployerName());
        $sheet->setCellValue('E3', 'Work Location Code');
        $sheet->setCellValue('G3', $settings->getWorkLocationCode());
        $sheet->setCellValue('A4', 'Work Location');
        $sheet->setCellValue('C4', $settings->getWorkLocation());
        $sheet->setCellValue('E4', 'Report Month');
        $sheet->setCellValue('G4', DateTime::createFromFormat('Y-m-d', $report['reportMonth']));
        $sheet->getStyle('G4')->getNumberFormat()->setFormatCode('MMMM YYYY');
        $sheet->setCellValue('A5', 'Union Contact(s)');
        $sheet->setCellValue('C5', $settings->getUnionContacts());
        $sheet->setCellValue('E5', 'Remittance Due');
        $sheet->setCellValue('G5', $report['remittanceDueDate']);
        $sheet->setCellValue('A6', 'Prepared By');
        $sheet->setCellValue('C6', $report['preparedBy']);
        $sheet->setCellValue('E6', 'Date Prepared');
        $sheet->setCellValue('G6', $report['datePrepared']);

        $sheet->setCellValue('A8', 'Remittance Totals');
        $sheet->getStyle('A8')->getFont()->setBold(true);
        $sheet->setCellValue('A10', 'Regular Union Dues');
        $sheet->setCellValue('B10', $report['totals']['unionDues']);
        $sheet->setCellValue('A11', 'Initiation Fees');
        $sheet->setCellValue('B11', $report['totals']['initiationFees']);
        $sheet->setCellValue('A12', 'Total Remittance');
        $sheet->setCellValue('B12', $report['totals']['remittance']);
        $sheet->getStyle('B10:B12')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->getStyle('A12:B12')->getFont()->setBold(true);

        $sheet->setCellValue('A14', 'Initiation Fee Rules');
        $sheet->getStyle('A14')->getFont()->setBold(true);
        $sheet->setCellValue('A15', 'Employee type');
        $sheet->setCellValue('B15', 'Total initiation fee');
        $sheet->setCellValue('C15', 'Maximum weekly deduction');
        $sheet->setCellValue('A16', 'Full-time');
        $sheet->setCellValue('B16', $settings->getInitiationFeeFullTime());
        $sheet->setCellValue('C16', $settings->getInitiationWeeklyMaxFullTime());
        $sheet->setCellValue('A17', 'Part-time');
        $sheet->setCellValue('B17', $settings->getInitiationFeePartTime());
        $sheet->setCellValue('C17', $settings->getInitiationWeeklyMaxPartTime());
        $sheet->getStyle('B16:C17')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

        $sheet->setCellValue('A19', 'Submission Instructions');
        $sheet->getStyle('A19')->getFont()->setBold(true);
        $sheet->setCellValue('A20', 'Deadline: 15th of the following month');
        $sheet->setCellValue('A21', 'Report email: ' . $settings->getRemittanceEmail());
        $sheet->setCellValue('A22', 'Cheque payable to: ' . $settings->getChequePayableTo());
        $sheet->setCellValue('A23', 'Attention: ' . $settings->getChequeAttention());
        $sheet->setCellValue('A24', 'Attach a hard copy of the detailed dues report to the cheque');
        $sheet->setCellValue('A25', 'Do not offset prior overpayments');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param UfcwRemittanceSettings $settings
     */
    private function buildInstructionsSheet($sheet, UfcwRemittanceSettings $settings): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'UFCW Monthly Remittance Report — Instructions');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Generate this workbook each month from Time → UFCW Monthly Remittance. Review yellow/red fields before emailing.');
        $lines = [
            5 => 'S.I.N. — Social Insurance Number (stored as text)',
            6 => 'Employee # — Internal employee ID',
            7 => 'Full Name — Last, First, Initials',
            8 => 'Contact fields — Telephone and email are required when an employee is listed',
            9 => 'Rate of Pay — Hourly rate used for the weekly dues formula',
            10 => 'FT/PT Designation — Full-time, Part-time, or Other / N/A',
            11 => 'Union Dues Deducted — Auto-calculated as weeks-with-hours × (rate × multiplier + flat fee)',
            12 => 'Initiation Fees Deducted — Capped by remaining balance and weekly maximums',
            13 => 'Total Deducted — Regular dues + initiation fees',
            14 => 'Reason No Deduction — Required when both deduction columns are zero',
            15 => 'Weekly dues formula — Hourly rate × '
                . $settings->getDuesHourlyMultiplier()
                . ' + $'
                . number_format($settings->getDuesWeeklyFlatFee(), 2)
                . ' (configurable in UFCW Remittance Settings)',
            16 => 'Remittance deadline — 15th of the month following the report month',
            17 => 'Bargaining-unit membership — Employees assigned the configured Admin membership type',
            18 => 'Ambiguous cases (multiple rates, status changes, etc.) are flagged for human review',
            20 => 'Example calculation: $20.00/hr → weekly dues = (20 × '
                . $settings->getDuesHourlyMultiplier()
                . ') + '
                . number_format($settings->getDuesWeeklyFlatFee(), 2)
                . ' = $'
                . number_format($this->calculateWeeklyDues(20.0, $settings), 2),
            21 => 'Full-time initiation fee: $' . number_format($settings->getInitiationFeeFullTime(), 2)
                . ' (max $' . number_format($settings->getInitiationWeeklyMaxFullTime(), 2) . '/week)',
            22 => 'Part-time initiation fee: $' . number_format($settings->getInitiationFeePartTime(), 2)
                . ' (max $' . number_format($settings->getInitiationWeeklyMaxPartTime(), 2) . '/week)',
            26 => 'Privacy — SIN and personal contact details must be transmitted securely and only to authorized recipients.',
            27 => 'Do not post this workbook to shared drives without access controls.',
            31 => 'Source — Approved timesheets, PIM employee master data, Admin membership assignments, and UFCW remittance settings.',
            33 => 'Overpayments must not be subtracted from the current remittance; the employee requests a refund from the union.',
            34 => 'Submit by email and send a hard copy with the cheque payable to ' . $settings->getChequePayableTo() . '.',
        ];
        foreach ($lines as $row => $text) {
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", $text);
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $title = str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $title);
        return substr($title, 0, 31);
    }

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
}
