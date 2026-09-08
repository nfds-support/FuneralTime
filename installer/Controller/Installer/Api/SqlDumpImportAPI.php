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

namespace OrangeHRM\Installer\Controller\Installer\Api;

use InvalidArgumentException;
use OrangeHRM\Config\Config;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Installer\Controller\AbstractInstallerRestController;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\Logger;
use OrangeHRM\Installer\Util\StateContainer;
use RuntimeException;
use Throwable;

class SqlDumpImportAPI extends AbstractInstallerRestController
{
    /**
     * Create the target database and import the previously uploaded SQL dump.
     *
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        if (Config::isInstalled()) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'This system is already installed.',
                ],
            ];
        }

        set_time_limit(0);
        $overwrite = $request->request->getBoolean('overwrite', false);
        $appSetupUtility = new AppSetupUtility();

        try {
            if (StateContainer::getInstance()->isSqlDumpImported()) {
                return [
                    'data' => [
                        'currentVersion' => StateContainer::getInstance()->getCurrentVersion(),
                        'skipAdminSeed' => StateContainer::getInstance()->shouldSkipAdminSeed(),
                        'alreadyImported' => true,
                    ],
                ];
            }
            $result = $appSetupUtility->createDatabaseAndImportSqlDump(null, $overwrite);
            return [
                'data' => array_merge($result, ['alreadyImported' => false]),
            ];
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        } catch (Throwable $e) {
            Logger::getLogger()->error($e->getMessage());
            Logger::getLogger()->error($e->getTraceAsString());
            $this->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'SQL dump import failed. Check installer.log for details.',
                ],
            ];
        }
    }
}
