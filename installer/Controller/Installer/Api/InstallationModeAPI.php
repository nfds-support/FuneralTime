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

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Installer\Controller\AbstractInstallerRestController;
use OrangeHRM\Installer\Util\StateContainer;

class InstallationModeAPI extends AbstractInstallerRestController
{
    /**
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        $mode = $request->request->get('mode');
        if (!in_array($mode, [
            StateContainer::INSTALLATION_MODE_FRESH,
            StateContainer::INSTALLATION_MODE_RESTORE,
        ], true)) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Invalid installation mode',
                ],
            ];
        }
        StateContainer::getInstance()->setInstallationMode($mode);
        if ($mode === StateContainer::INSTALLATION_MODE_FRESH) {
            StateContainer::getInstance()->clearSqlDumpPath();
            StateContainer::getInstance()->setSkipAdminSeed(false);
        }
        return ['data' => ['mode' => $mode]];
    }

    /**
     * @inheritDoc
     */
    protected function handleGet(Request $request): array
    {
        $state = StateContainer::getInstance();
        return [
            'data' => [
                'mode' => $state->getInstallationMode(),
                'skipAdminSeed' => $state->shouldSkipAdminSeed(),
                'sqlDumpImported' => $state->isSqlDumpImported(),
                'currentVersion' => $state->getCurrentVersion(),
                'hasDump' => $state->getSqlDumpPath() !== null,
            ],
        ];
    }
}
