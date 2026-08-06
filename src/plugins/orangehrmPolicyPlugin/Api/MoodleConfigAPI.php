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

namespace OrangeHRM\Policy\Api;

use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Policy\Api\Model\MoodleConfigModel;

class MoodleConfigAPI extends Endpoint implements ResourceEndpoint
{
    use ConfigServiceTrait;

    public const PARAMETER_BASE_URL = 'baseUrl';
    public const PARAMETER_WEBSERVICE_TOKEN = 'webserviceToken';
    public const PARAMETER_SYNC_ENABLED = 'syncEnabled';

    public function getOne(): EndpointResult
    {
        return new EndpointResourceResult(MoodleConfigModel::class, [
            'baseUrl' => $this->getConfigService()->getMoodleBaseUrl() ?? '',
            'webserviceTokenSet' => !empty($this->getConfigService()->getMoodleWebserviceToken()),
            'syncEnabled' => $this->getConfigService()->getMoodleSyncEnabled(),
        ]);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function update(): EndpointResult
    {
        $baseUrl = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_BASE_URL
        );
        if ($baseUrl !== null) {
            $this->getConfigService()->setMoodleBaseUrl($baseUrl);
        }
        $token = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_WEBSERVICE_TOKEN
        );
        if ($token !== null && $token !== '') {
            $this->getConfigService()->setMoodleWebserviceToken($token);
        }
        $syncEnabled = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SYNC_ENABLED
        );
        if ($syncEnabled !== null) {
            $this->getConfigService()->setMoodleSyncEnabled($syncEnabled);
        }
        return $this->getOne();
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_BASE_URL, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 512]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_WEBSERVICE_TOKEN, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 255]))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SYNC_ENABLED, new Rule(Rules::BOOL_VAL))
            ),
        );
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
