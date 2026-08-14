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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Time\Api\Model\TimesheetReminderConfigModel;
use OrangeHRM\Time\Traits\Service\TimesheetReminderServiceTrait;

class TimesheetReminderConfigAPI extends Endpoint implements ResourceEndpoint
{
    use TimesheetReminderServiceTrait;

    public const PARAMETER_ENABLED = 'enabled';
    public const PARAMETER_WEEKDAY = 'weekday';
    public const PARAMETER_SEND_TIME = 'sendTime';
    public const PARAMETER_TIMEZONE = 'timezone';
    public const PARAMETER_JOB_TITLE_IDS = 'jobTitleIds';
    public const PARAMETER_EMP_NUMBERS = 'empNumbers';

    /**
     * @OA\Get(
     *     path="/api/v2/time/config/timesheet-reminders",
     *     tags={"Time/Timesheet Reminders"},
     *     summary="Get timesheet reminder configuration",
     *     operationId="get-timesheet-reminder-config",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Time-TimesheetReminderConfigModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        return new EndpointResourceResult(
            TimesheetReminderConfigModel::class,
            $this->getTimesheetReminderService()->getConfig()
        );
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/time/config/timesheet-reminders",
     *     tags={"Time/Timesheet Reminders"},
     *     summary="Update timesheet reminder configuration",
     *     operationId="update-timesheet-reminder-config",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"enabled", "weekday", "sendTime", "timezone"},
     *             @OA\Property(property="enabled", type="boolean"),
     *             @OA\Property(property="weekday", type="integer", minimum=0, maximum=6),
     *             @OA\Property(property="sendTime", type="string", example="16:00"),
     *             @OA\Property(property="timezone", type="string"),
     *             @OA\Property(property="jobTitleIds", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="empNumbers", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Time-TimesheetReminderConfigModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $jobTitleIds = array_map(
            'intval',
            $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_JOB_TITLE_IDS)
        );
        $empNumbers = array_map(
            'intval',
            $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EMP_NUMBERS)
        );
        $enabled = $this->getRequestParams()->getBoolean(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ENABLED);

        if ($enabled && $jobTitleIds === [] && $empNumbers === []) {
            throw new BadRequestException('Select at least one job title or employee when reminders are enabled.');
        }

        $config = $this->getTimesheetReminderService()->getConfig();
        $config->setEnabled($enabled);
        $config->setWeekday(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_WEEKDAY)
        );
        $config->setSendTime(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SEND_TIME)
        );
        $config->setTimezone(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TIMEZONE)
        );
        $config->getDecorator()->setJobTitlesByIds($jobTitleIds);
        $config->getDecorator()->setEmployeesByEmpNumbers($empNumbers);
        $this->getTimesheetReminderService()->saveConfig($config);

        return new EndpointResourceResult(TimesheetReminderConfigModel::class, $config);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID),
            new ParamRule(self::PARAMETER_ENABLED, new Rule(Rules::BOOL_VAL)),
            new ParamRule(
                self::PARAMETER_WEEKDAY,
                new Rule(Rules::INT_VAL),
                new Rule(Rules::BETWEEN, [0, 6])
            ),
            new ParamRule(
                self::PARAMETER_SEND_TIME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, 5]),
                new Rule(Rules::REGEX, ['/^([01]\d|2[0-3]):[0-5]\d$/'])
            ),
            new ParamRule(
                self::PARAMETER_TIMEZONE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [1, 64])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_JOB_TITLE_IDS, new Rule(Rules::ARRAY_TYPE)),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMP_NUMBERS, new Rule(Rules::ARRAY_TYPE)),
                true
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
