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

namespace OrangeHRM\Time\Controller;

use DateTime;
use OrangeHRM\Core\Controller\AbstractFileController;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Time\Traits\Service\UfcwRemittanceReportServiceTrait;

class UfcwRemittanceReportDownloadController extends AbstractFileController
{
    use UfcwRemittanceReportServiceTrait;

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $monthParam = (string) $request->query->get('month', '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
            return $this->handleBadRequest();
        }

        $preparedBy = $request->query->get('preparedBy');
        $preparedBy = is_string($preparedBy) ? $preparedBy : null;

        $overrides = [];
        $overridesJson = $request->request->get('overrides');
        if (is_string($overridesJson) && $overridesJson !== '') {
            $decoded = json_decode($overridesJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (is_array($row) && isset($row['empNumber'])) {
                        $overrides[(int) $row['empNumber']] = $row;
                    }
                }
            }
        }

        // Also accept JSON body (axios POST)
        $content = $request->getContent();
        if ($content !== '' && $content !== false) {
            $body = json_decode($content, true);
            if (is_array($body)) {
                if (isset($body['month']) && is_string($body['month'])) {
                    $monthParam = $body['month'];
                    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
                        return $this->handleBadRequest();
                    }
                }
                if (isset($body['preparedBy']) && is_string($body['preparedBy'])) {
                    $preparedBy = $body['preparedBy'];
                }
                if (isset($body['employees']) && is_array($body['employees'])) {
                    foreach ($body['employees'] as $row) {
                        if (is_array($row) && isset($row['empNumber'])) {
                            $overrides[(int) $row['empNumber']] = $row;
                        }
                    }
                }
            }
        }

        $reportMonth = DateTime::createFromFormat('Y-m-d', $monthParam . '-01');
        if ($reportMonth === false) {
            return $this->handleBadRequest();
        }

        $reportService = $this->getUfcwRemittanceReportService();
        $content = $reportService->generateXlsx($reportMonth, $preparedBy, $overrides);
        $filename = $reportService->buildDownloadFilename($reportMonth);
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $response = $this->getResponse();
        $this->setCommonHeadersToResponse($filename, $contentType, (string) strlen($content), $response);
        $response->setContent($content);
        return $response;
    }
}
