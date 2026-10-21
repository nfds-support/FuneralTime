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

namespace OrangeHRM\Core\Api\Rest;

use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Service\UiLanguageService;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Framework\Services;

class UiLanguageAPI extends Endpoint implements ResourceEndpoint
{
    use ServiceContainerTrait;

    public const PARAMETER_LANGUAGE = 'language';

    private function getUiLanguageService(): UiLanguageService
    {
        return $this->getContainer()->get(Services::UI_LANGUAGE_SERVICE);
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $service = $this->getUiLanguageService();
        return new EndpointResourceResult(ArrayModel::class, [
            'language' => $service->resolveCurrentLanguage($this->getRequest()->getHttpRequest()),
            'languages' => $service->getSelectableLanguages(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('id', new Rule(Rules::EQUALS, [0]))
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $langCode = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_LANGUAGE);
        $service = $this->getUiLanguageService();
        $langCode = $service->setCurrentLanguage($langCode);

        return new EndpointResourceResult(ArrayModel::class, [
            'language' => $langCode,
            'languages' => $service->getSelectableLanguages(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('id', new Rule(Rules::EQUALS, [0])),
            new ParamRule(
                self::PARAMETER_LANGUAGE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::IN, [UiLanguageService::ALLOWED_CODES])
            )
        );
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
}
