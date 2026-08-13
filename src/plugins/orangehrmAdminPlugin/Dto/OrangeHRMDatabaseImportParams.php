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

namespace OrangeHRM\Admin\Dto;

class OrangeHRMDatabaseImportParams
{
    private string $host = '127.0.0.1';
    private int $port = 3306;
    private string $database = '';
    private string $username = '';
    private string $password = '';
    private bool $dryRun = false;
    private bool $activeEmployeesOnly = true;
    private bool $importJobTitles = true;
    private bool $importEmploymentStatuses = true;
    private bool $importJobCategories = true;
    private bool $importLocations = true;
    private bool $importEmployees = true;

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function isActiveEmployeesOnly(): bool
    {
        return $this->activeEmployeesOnly;
    }

    public function setActiveEmployeesOnly(bool $activeEmployeesOnly): void
    {
        $this->activeEmployeesOnly = $activeEmployeesOnly;
    }

    public function isImportJobTitles(): bool
    {
        return $this->importJobTitles;
    }

    public function setImportJobTitles(bool $importJobTitles): void
    {
        $this->importJobTitles = $importJobTitles;
    }

    public function isImportEmploymentStatuses(): bool
    {
        return $this->importEmploymentStatuses;
    }

    public function setImportEmploymentStatuses(bool $importEmploymentStatuses): void
    {
        $this->importEmploymentStatuses = $importEmploymentStatuses;
    }

    public function isImportJobCategories(): bool
    {
        return $this->importJobCategories;
    }

    public function setImportJobCategories(bool $importJobCategories): void
    {
        $this->importJobCategories = $importJobCategories;
    }

    public function isImportLocations(): bool
    {
        return $this->importLocations;
    }

    public function setImportLocations(bool $importLocations): void
    {
        $this->importLocations = $importLocations;
    }

    public function isImportEmployees(): bool
    {
        return $this->importEmployees;
    }

    public function setImportEmployees(bool $importEmployees): void
    {
        $this->importEmployees = $importEmployees;
    }
}
