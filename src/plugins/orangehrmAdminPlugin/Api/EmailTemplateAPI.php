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

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Admin\Api\Model\EmailTemplateModel;
use OrangeHRM\Admin\Service\EmailTemplateService;
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
use OrangeHRM\Core\Dto\EmailTemplateSearchFilterParams;
use OrangeHRM\Entity\Email;
use OrangeHRM\Entity\EmailTemplate;

class EmailTemplateAPI extends Endpoint implements CrudEndpoint
{
    public const PARAMETER_EMAIL_ID = 'emailId';
    public const PARAMETER_EMAIL_NAME = 'emailName';
    public const PARAMETER_LOCALE = 'locale';
    public const PARAMETER_PERFORMER_ROLE = 'performerRole';
    public const PARAMETER_RECIPIENT_ROLE = 'recipientRole';
    public const PARAMETER_SUBJECT = 'subject';
    public const PARAMETER_BODY = 'body';
    public const PARAMETER_RESET_TO_DEFAULT = 'resetToDefault';
    public const PARAMETER_EMAIL_NAME_FILTER = 'emailName';

    public const PARAM_RULE_EMAIL_NAME_MAX_LENGTH = 100;
    public const PARAM_RULE_LOCALE_MAX_LENGTH = 20;
    public const PARAM_RULE_ROLE_MAX_LENGTH = 50;
    public const PARAM_RULE_SUBJECT_MAX_LENGTH = 5000;
    public const PARAM_RULE_BODY_MAX_LENGTH = 100000;

    private ?EmailTemplateService $emailTemplateService = null;

    public function getEmailTemplateService(): EmailTemplateService
    {
        return $this->emailTemplateService ??= new EmailTemplateService();
    }

    /**
     * @OA\Get(
     *     path="/api/v2/admin/email-templates",
     *     tags={"Admin/Email Template"},
     *     summary="List Email Templates",
     *     operationId="list-email-templates",
     *     @OA\Parameter(name="emailName", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="locale", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new EmailTemplateSearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setEmailName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::PARAMETER_EMAIL_NAME_FILTER
            )
        );
        $filterParams->setLocale(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::PARAMETER_LOCALE
            )
        );

        $templates = $this->getEmailTemplateService()->searchEmailTemplates($filterParams);
        $count = $this->getEmailTemplateService()->getEmailTemplatesCount($filterParams);

        return new EndpointCollectionResult(
            EmailTemplateModel::class,
            $templates,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMAIL_NAME_FILTER, new Rule(Rules::STRING_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_LOCALE, new Rule(Rules::STRING_TYPE))
            ),
            ...$this->getSortingAndPaginationParamsRules(EmailTemplateSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/admin/email-templates/{id}",
     *     tags={"Admin/Email Template"},
     *     summary="Get an Email Template",
     *     operationId="get-email-template",
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $template = $this->getEmailTemplateService()->getEmailTemplateById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($template, EmailTemplate::class);
        return new EndpointResourceResult(EmailTemplateModel::class, $template);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/admin/email-templates",
     *     tags={"Admin/Email Template"},
     *     summary="Create an Email Template",
     *     operationId="create-email-template",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function create(): EndpointResult
    {
        $template = new EmailTemplate();
        $this->setTemplateFields($template, true);
        $this->getEmailTemplateService()->saveEmailTemplate($template);
        return new EndpointResourceResult(EmailTemplateModel::class, $template);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_EMAIL_ID, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EMAIL_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_EMAIL_NAME_MAX_LENGTH])
                )
            ),
            ...$this->getCommonBodyValidationRules(true)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/admin/email-templates/{id}",
     *     tags={"Admin/Email Template"},
     *     summary="Update an Email Template",
     *     operationId="update-email-template",
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $template = $this->getEmailTemplateService()->getEmailTemplateById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($template, EmailTemplate::class);

        $reset = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_RESET_TO_DEFAULT,
            false
        );
        if ($reset) {
            $this->getEmailTemplateService()->resetTemplateToDefault($template);
            return new EndpointResourceResult(EmailTemplateModel::class, $template);
        }

        $this->setTemplateFields($template, false);
        $this->getEmailTemplateService()->saveEmailTemplate($template);
        return new EndpointResourceResult(EmailTemplateModel::class, $template);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_RESET_TO_DEFAULT, new Rule(Rules::BOOL_TYPE))
            ),
            ...$this->getCommonBodyValidationRules(false)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/admin/email-templates",
     *     tags={"Admin/Email Template"},
     *     summary="Delete Email Templates",
     *     operationId="delete-email-templates",
     *     @OA\Response(response="200", description="Success")
     * )
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        $this->getEmailTemplateService()->deleteEmailTemplates($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    /**
     * @return ParamRule[]
     */
    private function getCommonBodyValidationRules(bool $create): array
    {
        $subjectRule = new ParamRule(
            self::PARAMETER_SUBJECT,
            new Rule(Rules::STRING_TYPE),
            new Rule(Rules::LENGTH, [null, self::PARAM_RULE_SUBJECT_MAX_LENGTH])
        );
        $bodyRule = new ParamRule(
            self::PARAMETER_BODY,
            new Rule(Rules::STRING_TYPE),
            new Rule(Rules::LENGTH, [null, self::PARAM_RULE_BODY_MAX_LENGTH])
        );

        return [
            $create
                ? $this->getValidationDecorator()->requiredParamRule(
                    new ParamRule(
                        self::PARAMETER_LOCALE,
                        new Rule(Rules::STRING_TYPE),
                        new Rule(Rules::LENGTH, [1, self::PARAM_RULE_LOCALE_MAX_LENGTH])
                    )
                )
                : $this->getValidationDecorator()->notRequiredParamRule(
                    new ParamRule(
                        self::PARAMETER_LOCALE,
                        new Rule(Rules::STRING_TYPE),
                        new Rule(Rules::LENGTH, [null, self::PARAM_RULE_LOCALE_MAX_LENGTH])
                    )
                ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_PERFORMER_ROLE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_ROLE_MAX_LENGTH])
                ),
                true
            ),
            $create
                ? $this->getValidationDecorator()->requiredParamRule(
                    new ParamRule(
                        self::PARAMETER_RECIPIENT_ROLE,
                        new Rule(Rules::STRING_TYPE),
                        new Rule(Rules::LENGTH, [1, self::PARAM_RULE_ROLE_MAX_LENGTH])
                    )
                )
                : $this->getValidationDecorator()->notRequiredParamRule(
                    new ParamRule(
                        self::PARAMETER_RECIPIENT_ROLE,
                        new Rule(Rules::STRING_TYPE),
                        new Rule(Rules::LENGTH, [null, self::PARAM_RULE_ROLE_MAX_LENGTH])
                    ),
                    true
                ),
            $create
                ? $this->getValidationDecorator()->requiredParamRule($subjectRule)
                : $this->getValidationDecorator()->notRequiredParamRule($subjectRule),
            $create
                ? $this->getValidationDecorator()->requiredParamRule($bodyRule)
                : $this->getValidationDecorator()->notRequiredParamRule($bodyRule),
        ];
    }

    private function setTemplateFields(EmailTemplate $template, bool $create): void
    {
        if ($create) {
            $emailId = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMAIL_ID
            );
            $emailName = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMAIL_NAME
            );
            if ($emailId !== null) {
                $email = $this->getEmailTemplateService()->getEmailDao()->getEmailById($emailId);
                $this->throwRecordNotFoundExceptionIfNotExist($email, Email::class);
            } elseif (!empty($emailName)) {
                $email = $this->getEmailTemplateService()->getOrCreateEmailByName(trim($emailName));
            } else {
                throw $this->getInvalidParamException([self::PARAMETER_EMAIL_ID, self::PARAMETER_EMAIL_NAME]);
            }
            $template->setEmail($email);
            $template->setSubject('');
            $template->setBody('');
        }

        $locale = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_LOCALE
        );
        if ($locale !== null) {
            $template->setLocale($locale);
        }

        if ($this->getRequestParams()->has(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PERFORMER_ROLE)) {
            $performerRole = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_PERFORMER_ROLE
            );
            $template->setPerformerRole($performerRole === '' ? null : $performerRole);
        }

        if ($this->getRequestParams()->has(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_RECIPIENT_ROLE)) {
            $recipientRole = $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_RECIPIENT_ROLE
            );
            $template->setRecipientRole((string)$recipientRole);
        }

        if ($this->getRequestParams()->has(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SUBJECT)) {
            $template->setSubjectContent(
                $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_SUBJECT)
            );
        }
        if ($this->getRequestParams()->has(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_BODY)) {
            $template->setBodyContent(
                $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_BODY)
            );
        }
    }
}
