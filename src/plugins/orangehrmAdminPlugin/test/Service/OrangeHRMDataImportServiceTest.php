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

namespace OrangeHRM\Tests\Admin\Service;

use Exception;
use OrangeHRM\Admin\Dto\OrangeHRMDatabaseImportParams;
use OrangeHRM\Admin\Service\OrangeHRMDataImportService;
use OrangeHRM\Pim\Service\PimCsvDataImportService;
use OrangeHRM\Tests\Util\TestCase;

/**
 * @group Admin
 * @group Service
 */
class OrangeHRMDataImportServiceTest extends TestCase
{
    public function testImportEmployeeCsvDelegatesToPimService(): void
    {
        $pimService = $this->getMockBuilder(PimCsvDataImportService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['import'])
            ->getMock();
        $pimService->expects($this->once())
            ->method('import')
            ->with('csv-content')
            ->willReturn(['success' => 2, 'failed' => 1, 'failedRows' => [3]]);

        $service = new OrangeHRMDataImportService();
        $service->setPimCsvDataImportService($pimService);

        $result = $service->importEmployeeCsv('csv-content');
        $this->assertSame(2, $result['success']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame([3], $result['failedRows']);
    }

    public function testConnectRequiresHostDatabaseAndUsername(): void
    {
        $service = new OrangeHRMDataImportService();
        $params = new OrangeHRMDatabaseImportParams();
        $params->setHost('');
        $params->setDatabase('ohrm');
        $params->setUsername('root');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Host, database name, and username are required');
        $service->connect($params);
    }

    public function testDatabaseImportParamsDefaults(): void
    {
        $params = new OrangeHRMDatabaseImportParams();
        $this->assertSame('127.0.0.1', $params->getHost());
        $this->assertSame(3306, $params->getPort());
        $this->assertTrue($params->isActiveEmployeesOnly());
        $this->assertTrue($params->isImportEmployees());
        $this->assertFalse($params->isDryRun());
    }
}
