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

namespace OrangeHRM\Time\Api;

use DateTime;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetDay;
use OrangeHRM\Leave\Traits\Service\HolidayServiceTrait;
use OrangeHRM\Time\Traits\Service\TimesheetServiceTrait;

class TimesheetDayAPI extends Endpoint implements CrudEndpoint
{
    use TimesheetServiceTrait;
    use UserRoleManagerTrait;
    use HolidayServiceTrait;
    use DateTimeHelperTrait;

    public const PARAMETER_TIMESHEET_ID = 'timesheetId';
    public const PARAMETER_DAYS = 'days';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $dayMetaByDate = [];
        foreach ($this->getTimesheetService()->getTimesheetDao()->getTimesheetDaysByTimesheetId($timesheet->getId()) as $day) {
            $dayMetaByDate[$day->getDate()->format('Y-m-d')] = [
                'onCall' => $day->isOnCall(),
                'breakDuration' => $this->formatSecondsAsDuration($day->getBreakDuration()),
            ];
        }

        $result = [];
        foreach ($this->getDateTimeHelper()->dateRange($timesheet->getStartDate(), $timesheet->getEndDate()) as $date) {
            $ymd = $date->format('Y-m-d');
            $holiday = $this->getHolidayService()->getHolidayDao()->getHolidayByDate($date);
            $result[] = [
                'date' => $ymd,
                'onCall' => $dayMetaByDate[$ymd]['onCall'] ?? false,
                'breakDuration' => $dayMetaByDate[$ymd]['breakDuration'] ?? '00:00',
                'isHoliday' => $this->getHolidayService()->isHoliday($date)
                    || $this->getHolidayService()->isHalfDayHoliday($date),
                'holidayName' => $holiday?->getName(),
            ];
        }

        return new EndpointCollectionResult(
            ArrayModel::class,
            $result,
            new ParameterBag(['total' => count($result)])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_TIMESHEET_ID, new Rule(Rules::POSITIVE))
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $days = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DAYS);
        $saved = [];
        foreach ($days as $dayPayload) {
            if (!isset($dayPayload['date'])) {
                continue;
            }
            $date = new DateTime($dayPayload['date']);
            if ($date < $timesheet->getStartDate() || $date > $timesheet->getEndDate()) {
                continue;
            }
            $timesheetDay = $this->getTimesheetService()
                ->getTimesheetDao()
                ->getTimesheetDayByTimesheetIdAndDate($timesheet->getId(), $date);
            if (!$timesheetDay instanceof TimesheetDay) {
                $timesheetDay = new TimesheetDay();
                $timesheetDay->setTimesheet($timesheet);
                $timesheetDay->setDate($date);
            }
            if (array_key_exists('onCall', $dayPayload)) {
                $timesheetDay->setOnCall((bool) $dayPayload['onCall']);
            }
            if (array_key_exists('breakDuration', $dayPayload)) {
                $timesheetDay->setBreakDuration($this->parseDurationToSeconds((string) $dayPayload['breakDuration']));
            }
            $this->getTimesheetService()->getTimesheetDao()->saveTimesheetDay($timesheetDay);
            $holiday = $this->getHolidayService()->getHolidayDao()->getHolidayByDate($date);
            $saved[] = [
                'date' => $date->format('Y-m-d'),
                'onCall' => $timesheetDay->isOnCall(),
                'breakDuration' => $this->formatSecondsAsDuration($timesheetDay->getBreakDuration()),
                'isHoliday' => $this->getHolidayService()->isHoliday($date)
                    || $this->getHolidayService()->isHalfDayHoliday($date),
                'holidayName' => $holiday?->getName(),
            ];
        }

        return new EndpointResourceResult(ArrayModel::class, $saved);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_TIMESHEET_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_DAYS, new Rule(Rules::ARRAY_TYPE)),
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    private function getAccessibleTimesheet(): Timesheet
    {
        $timesheetId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_TIMESHEET_ID
        );
        $timesheet = $this->getTimesheetService()->getTimesheetDao()->getTimesheetById($timesheetId);
        $this->throwRecordNotFoundExceptionIfNotExist($timesheet, Timesheet::class);
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($timesheet->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        return $timesheet;
    }

    private function parseDurationToSeconds(string $duration): int
    {
        if ($duration === '' || $duration === '00:00') {
            return 0;
        }
        if (!preg_match('/^(\d{1,2}):([0-5]\d)$/', $duration, $matches)) {
            return 0;
        }
        return ((int) $matches[1]) * 3600 + ((int) $matches[2]) * 60;
    }

    private function formatSecondsAsDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
