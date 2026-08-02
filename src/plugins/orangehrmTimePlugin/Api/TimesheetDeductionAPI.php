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
use OrangeHRM\Core\Api\CommonParams;
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
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Timesheet;
use OrangeHRM\Entity\TimesheetDeduction;
use OrangeHRM\Time\Api\Model\TotalDurationModel;
use OrangeHRM\Time\Traits\Service\TimesheetServiceTrait;
use OrangeHRM\Core\Traits\Service\NormalizerServiceTrait;

class TimesheetDeductionAPI extends Endpoint implements CrudEndpoint
{
    use TimesheetServiceTrait;
    use UserRoleManagerTrait;
    use NormalizerServiceTrait;

    public const PARAMETER_TIMESHEET_ID = 'timesheetId';
    public const PARAMETER_DATE = 'date';
    public const PARAMETER_START_TIME = 'startTime';
    public const PARAMETER_END_TIME = 'endTime';
    public const PARAMETER_REASON = 'reason';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $deductions = $this->getTimesheetService()
            ->getTimesheetDao()
            ->getTimesheetDeductionsByTimesheetId($timesheet->getId());

        return new EndpointCollectionResult(
            ArrayModel::class,
            array_map(fn (TimesheetDeduction $d) => $this->normalize($d), $deductions),
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($deductions)])
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
    public function create(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $deduction = new TimesheetDeduction();
        $deduction->setTimesheet($timesheet);
        $this->setDeductionFields($deduction);
        $this->getTimesheetService()->getTimesheetDao()->saveTimesheetDeduction($deduction);
        return new EndpointResourceResult(ArrayModel::class, $this->normalize($deduction));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return $this->getBodyValidationRules();
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $deduction = $this->getTimesheetService()->getTimesheetDao()->getTimesheetDeductionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($deduction, TimesheetDeduction::class);
        if ($deduction->getTimesheet()->getId() !== $timesheet->getId()) {
            throw $this->getRecordNotFoundException();
        }
        return new EndpointResourceResult(ArrayModel::class, $this->normalize($deduction));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_TIMESHEET_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $deduction = $this->getTimesheetService()->getTimesheetDao()->getTimesheetDeductionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($deduction, TimesheetDeduction::class);
        if ($deduction->getTimesheet()->getId() !== $timesheet->getId()) {
            throw $this->getRecordNotFoundException();
        }
        $this->setDeductionFields($deduction);
        $this->getTimesheetService()->getTimesheetDao()->saveTimesheetDeduction($deduction);
        return new EndpointResourceResult(ArrayModel::class, $this->normalize($deduction));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return $this->getBodyValidationRules(true);
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $timesheet = $this->getAccessibleTimesheet();
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $deleted = $this->getTimesheetService()->getTimesheetDao()->deleteTimesheetDeductions(
            $timesheet->getId(),
            $ids
        );
        return new EndpointResourceResult(ArrayModel::class, ['deleted' => $deleted]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_TIMESHEET_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE)),
        );
    }

    private function getBodyValidationRules(bool $withId = false): ParamRuleCollection
    {
        $rules = [
            new ParamRule(self::PARAMETER_TIMESHEET_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_DATE, new Rule(Rules::API_DATE)),
            new ParamRule(self::PARAMETER_START_TIME, new Rule(Rules::TIME, ['H:i'])),
            new ParamRule(self::PARAMETER_END_TIME, new Rule(Rules::TIME, ['H:i'])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_REASON, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 255]))
            ),
        ];
        if ($withId) {
            $rules[] = new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
        }
        return new ParamRuleCollection(...$rules);
    }

    private function setDeductionFields(TimesheetDeduction $deduction): void
    {
        $date = $this->getRequestParams()->getDateTime(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DATE);
        $start = new DateTime(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_START_TIME)
        );
        $end = new DateTime(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_END_TIME)
        );
        $duration = $end->getTimestamp() - $start->getTimestamp();
        if ($duration < 0) {
            $duration += 86400;
        }
        $deduction->setDate($date);
        $deduction->setStartTime($start);
        $deduction->setEndTime($end);
        $deduction->setDuration($duration);
        $deduction->setReason(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_REASON)
        );
    }

    private function normalize(TimesheetDeduction $deduction): array
    {
        return [
            'id' => $deduction->getId(),
            'date' => $deduction->getDate()->format('Y-m-d'),
            'startTime' => $deduction->getStartTime()->format('H:i'),
            'endTime' => $deduction->getEndTime()->format('H:i'),
            'duration' => $this->getNormalizerService()->normalize(
                TotalDurationModel::class,
                $deduction->getDuration()
            ),
            'reason' => $deduction->getReason(),
        ];
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
}
