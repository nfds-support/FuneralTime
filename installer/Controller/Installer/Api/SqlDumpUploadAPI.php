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

use OrangeHRM\Config\Config;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Installer\Controller\AbstractInstallerRestController;
use OrangeHRM\Installer\Util\AppSetupUtility;
use OrangeHRM\Installer\Util\StateContainer;
use Throwable;

class SqlDumpUploadAPI extends AbstractInstallerRestController
{
    /**
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
        if (!StateContainer::getInstance()->isSetDbInfo()) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Database configuration is required before uploading a dump.',
                ],
            ];
        }

        $file = $request->files->get('sqlDump');
        if ($file === null) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Missing upload field `sqlDump`.',
                ],
            ];
        }
        if (!$file->isValid()) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Upload failed: ' . $file->getErrorMessage(),
                ],
            ];
        }

        $originalName = (string) $file->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Only .sql files are allowed.',
                ],
            ];
        }

        try {
            StateContainer::getInstance()->clearSqlDumpPath();
            $destDir = AppSetupUtility::getSqlDumpTempDir();
            $destName = 'dump_' . bin2hex(random_bytes(8)) . '.sql';
            $file->move($destDir, $destName);
            $path = $destDir . DIRECTORY_SEPARATOR . $destName;
            StateContainer::getInstance()->setSqlDumpPath($path, $originalName);
            StateContainer::getInstance()->setInstallationMode(StateContainer::INSTALLATION_MODE_RESTORE);
        } catch (Throwable $e) {
            $this->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Failed to store uploaded SQL dump.',
                ],
            ];
        }

        return [
            'data' => [
                'filename' => $originalName,
                'size' => filesize($path) ?: 0,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function handleGet(Request $request): array
    {
        $state = StateContainer::getInstance();
        return [
            'data' => [
                'hasDump' => $state->getSqlDumpPath() !== null,
                'filename' => $state->getSqlDumpOriginalName(),
                'installationMode' => $state->getInstallationMode(),
                'skipAdminSeed' => $state->shouldSkipAdminSeed(),
                'sqlDumpImported' => $state->isSqlDumpImported(),
                'currentVersion' => $state->getCurrentVersion(),
            ],
        ];
    }
}
