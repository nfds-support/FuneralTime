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

use Exception;
use OpenApi\Annotations as OA;
use OrangeHRM\Admin\Dto\OrangeHRMDatabaseImportParams;
use OrangeHRM\Admin\Service\OrangeHRMDataImportService;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\ORM\Exception\TransactionException;

class OrangeHRMDataImportDatabaseAPI extends Endpoint implements CollectionEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_HOST = 'host';
    public const PARAMETER_PORT = 'port';
    public const PARAMETER_DATABASE = 'database';
    public const PARAMETER_USERNAME = 'username';
    public const PARAMETER_PASSWORD = 'password';
    public const PARAMETER_DRY_RUN = 'dryRun';
    public const PARAMETER_ACTIVE_EMPLOYEES_ONLY = 'activeEmployeesOnly';
    public const PARAMETER_IMPORT_JOB_TITLES = 'importJobTitles';
    public const PARAMETER_IMPORT_EMPLOYMENT_STATUSES = 'importEmploymentStatuses';
    public const PARAMETER_IMPORT_JOB_CATEGORIES = 'importJobCategories';
    public const PARAMETER_IMPORT_LOCATIONS = 'importLocations';
    public const PARAMETER_IMPORT_EMPLOYEES = 'importEmployees';

    private ?OrangeHRMDataImportService $orangeHRMDataImportService = null;

    public function getOrangeHRMDataImportService(): OrangeHRMDataImportService
    {
        return $this->orangeHRMDataImportService ??= new OrangeHRMDataImportService();
    }

    public function setOrangeHRMDataImportService(OrangeHRMDataImportService $service): void
    {
        $this->orangeHRMDataImportService = $service;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @OA\Post(
     *     path="/api/v2/admin/orangehrm-import/database",
     *     tags={"Admin/OrangeHRM Data Import"},
     *     summary="Import data from another OrangeHRM MySQL database",
     *     operationId="import-orangehrm-database",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="host", type="string"),
     *             @OA\Property(property="port", type="integer"),
     *             @OA\Property(property="database", type="string"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="dryRun", type="boolean"),
     *             @OA\Property(property="activeEmployeesOnly", type="boolean"),
     *             @OA\Property(property="importJobTitles", type="boolean"),
     *             @OA\Property(property="importEmploymentStatuses", type="boolean"),
     *             @OA\Property(property="importJobCategories", type="boolean"),
     *             @OA\Property(property="importLocations", type="boolean"),
     *             @OA\Property(property="importEmployees", type="boolean"),
     *             required={"host", "database", "username"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     * @throws Exception
     */
    public function create(): EndpointResult
    {
        $params = $this->buildParams();
        $dryRun = $params->isDryRun();

        if (!$dryRun) {
            $this->beginTransaction();
        }
        try {
            $result = $this->getOrangeHRMDataImportService()->importFromDatabase($params);
            if (!$dryRun) {
                $this->commitTransaction();
            }
        } catch (BadRequestException $e) {
            if (!$dryRun) {
                $this->rollBackTransaction();
            }
            throw $e;
        } catch (Exception $e) {
            if (!$dryRun) {
                $this->rollBackTransaction();
            }
            throw new BadRequestException($e->getMessage());
        } catch (\Throwable $e) {
            if (!$dryRun) {
                $this->rollBackTransaction();
            }
            throw new TransactionException($e);
        }

        return new EndpointResourceResult(
            ArrayModel::class,
            $result,
            new ParameterBag([])
        );
    }

    private function buildParams(): OrangeHRMDatabaseImportParams
    {
        $params = new OrangeHRMDatabaseImportParams();
        $params->setHost(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_HOST)
        );
        $params->setPort(
            $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PORT) ?? 3306
        );
        $params->setDatabase(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DATABASE)
        );
        $params->setUsername(
            $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_USERNAME)
        );
        $params->setPassword(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_PASSWORD) ?? ''
        );
        $params->setDryRun(
            $this->getRequestParams()->getBoolean(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DRY_RUN, false)
        );
        $params->setActiveEmployeesOnly(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_ACTIVE_EMPLOYEES_ONLY,
                true
            )
        );
        $params->setImportJobTitles(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_IMPORT_JOB_TITLES,
                true
            )
        );
        $params->setImportEmploymentStatuses(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_IMPORT_EMPLOYMENT_STATUSES,
                true
            )
        );
        $params->setImportJobCategories(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_IMPORT_JOB_CATEGORIES,
                true
            )
        );
        $params->setImportLocations(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_IMPORT_LOCATIONS,
                true
            )
        );
        $params->setImportEmployees(
            $this->getRequestParams()->getBoolean(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_IMPORT_EMPLOYEES,
                true
            )
        );
        return $params;
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_HOST, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 255])),
            new ParamRule(self::PARAMETER_DATABASE, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 64])),
            new ParamRule(self::PARAMETER_USERNAME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [1, 64])),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_PASSWORD, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [0, 255])),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_PORT,
                    new Rule(Rules::INT_VAL),
                    new Rule(Rules::BETWEEN, [1, 65535])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_DRY_RUN, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_ACTIVE_EMPLOYEES_ONLY, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_IMPORT_JOB_TITLES, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_IMPORT_EMPLOYMENT_STATUSES, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_IMPORT_JOB_CATEGORIES, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_IMPORT_LOCATIONS, new Rule(Rules::BOOL_VAL))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_IMPORT_EMPLOYEES, new Rule(Rules::BOOL_VAL))
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

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
