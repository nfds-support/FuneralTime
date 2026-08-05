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

namespace OrangeHRM\Claim\Controller;

use OrangeHRM\Claim\Traits\Service\ExpenseClaimReportServiceTrait;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;

class MonthlyExpenseReportDownloadController extends AbstractFileController
{
    use ExpenseClaimReportServiceTrait;
    use UserRoleManagerTrait;
    use AuthUserTrait;
    use EmployeeServiceTrait;

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $empNumber = $request->query->getInt('empNumber', 0);
        $monthParam = (string) $request->query->get('month', '');
        $format = strtolower((string) $request->query->get('format', 'pdf'));

        if ($empNumber <= 0 || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
            return $this->handleBadRequest();
        }
        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            return $this->handleBadRequest();
        }
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
            return $this->handleBadRequest();
        }

        [$year, $month] = array_map('intval', explode('-', $monthParam));
        $employee = $this->getEmployeeService()->getEmployeeDao()->getEmployeeByEmpNumber($empNumber);
        if ($employee === null) {
            return $this->handleBadRequest();
        }

        $reportService = $this->getExpenseClaimReportService();
        if ($format === 'xlsx') {
            $content = $reportService->generateXlsx($empNumber, $year, $month);
            $filename = $reportService->buildDownloadFilename($employee, $year, $month, 'xlsx');
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $content = $reportService->generateMonthlyPdf($empNumber, $year, $month);
            $filename = $reportService->buildDownloadFilename($employee, $year, $month, 'pdf');
            $contentType = 'application/pdf';
        }

        $response = $this->getResponse();
        $this->setCommonHeadersToResponse($filename, $contentType, (string) strlen($content), $response);
        $response->setContent($content);
        return $response;
    }
}
