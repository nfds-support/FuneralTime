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

namespace OrangeHRM\Admin\Service;

use DateTime;
use Exception;
use OrangeHRM\Admin\Dto\EmploymentStatusSearchFilterParams;
use OrangeHRM\Admin\Dto\OrangeHRMDatabaseImportParams;
use OrangeHRM\Core\Traits\LoggerTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Entity\Country;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\EmploymentStatus;
use OrangeHRM\Entity\JobCategory;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\Location;
use OrangeHRM\Entity\Nationality;
use OrangeHRM\Framework\Services;
use OrangeHRM\Pim\Service\EmployeeService;
use OrangeHRM\Pim\Service\PimCsvDataImportService;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;
use PDO;
use PDOException;
use Throwable;

class OrangeHRMDataImportService
{
    use EntityManagerHelperTrait;
    use EmployeeServiceTrait;
    use LoggerTrait;
    use ServiceContainerTrait;

    public const SOURCE_REQUIRED_TABLES = [
        'hs_hr_employee',
        'ohrm_job_title',
        'ohrm_employment_status',
        'ohrm_job_category',
        'ohrm_location',
    ];

    private ?PimCsvDataImportService $pimCsvDataImportService = null;
    private ?JobTitleService $jobTitleService = null;
    private ?EmploymentStatusService $employmentStatusService = null;
    private ?JobCategoryService $jobCategoryService = null;
    private ?LocationService $locationService = null;

    /**
     * @param string $fileContent
     * @return array{success:int,failed:int,failedRows:int[]}
     * @throws Exception
     */
    public function importEmployeeCsv(string $fileContent): array
    {
        return $this->getPimCsvDataImportService()->import($fileContent);
    }

    public function getPimCsvDataImportService(): PimCsvDataImportService
    {
        return $this->pimCsvDataImportService ??= new PimCsvDataImportService();
    }

    public function setPimCsvDataImportService(PimCsvDataImportService $service): void
    {
        $this->pimCsvDataImportService = $service;
    }

    /**
     * @param OrangeHRMDatabaseImportParams $params
     * @return array<string, mixed>
     * @throws Exception
     */
    public function importFromDatabase(OrangeHRMDatabaseImportParams $params): array
    {
        $pdo = $this->connect($params);
        $this->assertOrangeHRMSchema($pdo);

        $preview = $this->buildPreview($pdo, $params);
        if ($params->isDryRun()) {
            return [
                'dryRun' => true,
                'preview' => $preview,
                'imported' => [],
                'skipped' => [],
                'failed' => [],
            ];
        }

        $imported = [
            'jobTitles' => 0,
            'employmentStatuses' => 0,
            'jobCategories' => 0,
            'locations' => 0,
            'employees' => 0,
        ];
        $skipped = [
            'jobTitles' => 0,
            'employmentStatuses' => 0,
            'jobCategories' => 0,
            'locations' => 0,
            'employees' => 0,
        ];
        $failed = [
            'employees' => [],
        ];

        $jobTitleMap = [];
        $empStatusMap = [];
        $locationMap = [];

        if ($params->isImportJobTitles()) {
            [$imported['jobTitles'], $skipped['jobTitles'], $jobTitleMap] = $this->importJobTitles($pdo);
        } else {
            $jobTitleMap = $this->buildLocalJobTitleMap();
        }

        if ($params->isImportEmploymentStatuses()) {
            [$imported['employmentStatuses'], $skipped['employmentStatuses'], $empStatusMap] = $this->importEmploymentStatuses($pdo);
        } else {
            $empStatusMap = $this->buildLocalEmploymentStatusMap();
        }

        if ($params->isImportJobCategories()) {
            [$imported['jobCategories'], $skipped['jobCategories']] = $this->importJobCategories($pdo);
        }

        if ($params->isImportLocations()) {
            [$imported['locations'], $skipped['locations'], $locationMap] = $this->importLocations($pdo);
        } else {
            $locationMap = $this->buildLocalLocationMap();
        }

        if ($params->isImportEmployees()) {
            [$imported['employees'], $skipped['employees'], $failed['employees']] = $this->importEmployees(
                $pdo,
                $params,
                $jobTitleMap,
                $empStatusMap,
                $locationMap
            );
        }

        return [
            'dryRun' => false,
            'preview' => $preview,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * @param OrangeHRMDatabaseImportParams $params
     * @return PDO
     * @throws Exception
     */
    public function connect(OrangeHRMDatabaseImportParams $params): PDO
    {
        if ($params->getHost() === '' || $params->getDatabase() === '' || $params->getUsername() === '') {
            throw new Exception('Host, database name, and username are required');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $params->getHost(),
            $params->getPort(),
            $params->getDatabase()
        );

        try {
            $pdo = new PDO(
                $dsn,
                $params->getUsername(),
                $params->getPassword(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 10,
                ]
            );
        } catch (PDOException $e) {
            $this->getLogger()->error('OrangeHRM source DB connection failed: ' . $e->getMessage());
            throw new Exception('Could not connect to the source OrangeHRM database. Check host, credentials, and network access.');
        }

        return $pdo;
    }

    /**
     * @param PDO $pdo
     * @throws Exception
     */
    public function assertOrangeHRMSchema(PDO $pdo): void
    {
        foreach (self::SOURCE_REQUIRED_TABLES as $table) {
            $stmt = $pdo->query(sprintf("SHOW TABLES LIKE %s", $pdo->quote($table)));
            if ($stmt === false || $stmt->fetch() === false) {
                throw new Exception(
                    sprintf(
                        'Source database does not look like a standard OrangeHRM installation (missing table "%s").',
                        $table
                    )
                );
            }
        }
    }

    /**
     * @param PDO $pdo
     * @param OrangeHRMDatabaseImportParams $params
     * @return array<string, int>
     */
    private function buildPreview(PDO $pdo, OrangeHRMDatabaseImportParams $params): array
    {
        $employeeSql = 'SELECT COUNT(*) FROM hs_hr_employee';
        if ($params->isActiveEmployeesOnly()) {
            $employeeSql .= ' WHERE termination_id IS NULL';
        }

        return [
            'jobTitles' => (int) $pdo->query(
                'SELECT COUNT(*) FROM ohrm_job_title WHERE is_deleted = 0 OR is_deleted IS NULL'
            )->fetchColumn(),
            'employmentStatuses' => (int) $pdo->query('SELECT COUNT(*) FROM ohrm_employment_status')->fetchColumn(),
            'jobCategories' => (int) $pdo->query('SELECT COUNT(*) FROM ohrm_job_category')->fetchColumn(),
            'locations' => (int) $pdo->query('SELECT COUNT(*) FROM ohrm_location')->fetchColumn(),
            'employees' => (int) $pdo->query($employeeSql)->fetchColumn(),
        ];
    }

    /**
     * @param PDO $pdo
     * @return array{0:int,1:int,2:array<string,JobTitle>}
     */
    private function importJobTitles(PDO $pdo): array
    {
        $map = $this->buildLocalJobTitleMap();
        $imported = 0;
        $skipped = 0;
        $rows = $pdo->query(
            'SELECT job_title, job_description, note FROM ohrm_job_title WHERE is_deleted = 0 OR is_deleted IS NULL'
        )->fetchAll();

        foreach ($rows as $row) {
            $name = trim((string) ($row['job_title'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($map[$key])) {
                $skipped++;
                continue;
            }
            $jobTitle = new JobTitle();
            $jobTitle->setJobTitleName($name);
            $jobTitle->setJobDescription($row['job_description'] !== null ? (string) $row['job_description'] : null);
            $jobTitle->setNote($row['note'] !== null ? (string) $row['note'] : null);
            $this->getJobTitleService()->saveJobTitle($jobTitle);
            $map[$key] = $jobTitle;
            $imported++;
        }

        return [$imported, $skipped, $map];
    }

    /**
     * @param PDO $pdo
     * @return array{0:int,1:int,2:array<string,EmploymentStatus>}
     */
    private function importEmploymentStatuses(PDO $pdo): array
    {
        $map = $this->buildLocalEmploymentStatusMap();
        $imported = 0;
        $skipped = 0;
        $rows = $pdo->query('SELECT name FROM ohrm_employment_status')->fetchAll();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($map[$key])) {
                $skipped++;
                continue;
            }
            $status = new EmploymentStatus();
            $status->setName($name);
            $this->getEmploymentStatusService()->saveEmploymentStatus($status);
            $map[$key] = $status;
            $imported++;
        }

        return [$imported, $skipped, $map];
    }

    /**
     * @param PDO $pdo
     * @return array{0:int,1:int}
     */
    private function importJobCategories(PDO $pdo): array
    {
        $existing = [];
        foreach ($this->getJobCategoryService()->getJobCategoryList() as $category) {
            $existing[mb_strtolower($category->getName())] = true;
        }
        $imported = 0;
        $skipped = 0;
        $rows = $pdo->query('SELECT name FROM ohrm_job_category')->fetchAll();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($existing[$key])) {
                $skipped++;
                continue;
            }
            $category = new JobCategory();
            $category->setName($name);
            $this->getJobCategoryService()->saveJobCategory($category);
            $existing[$key] = true;
            $imported++;
        }

        return [$imported, $skipped];
    }

    /**
     * @param PDO $pdo
     * @return array{0:int,1:int,2:array<string,Location>}
     */
    private function importLocations(PDO $pdo): array
    {
        $map = $this->buildLocalLocationMap();
        $imported = 0;
        $skipped = 0;
        $rows = $pdo->query(
            'SELECT name, country_code, province, city, address, zip_code, phone, fax, note FROM ohrm_location'
        )->fetchAll();

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($map[$key])) {
                $skipped++;
                continue;
            }
            $countryCode = trim((string) ($row['country_code'] ?? ''));
            $country = $countryCode !== ''
                ? $this->getCountryService()->getCountryByCountryCode($countryCode)
                : null;
            if (!$country instanceof Country) {
                $this->getLogger()->warning(sprintf('Skipping location "%s": unknown country code "%s"', $name, $countryCode));
                $skipped++;
                continue;
            }
            $location = new Location();
            $location->setName($name);
            $location->setCountry($country);
            $location->setProvince($row['province'] !== null ? (string) $row['province'] : null);
            $location->setCity($row['city'] !== null ? (string) $row['city'] : null);
            $location->setAddress($row['address'] !== null ? (string) $row['address'] : null);
            $location->setZipCode($row['zip_code'] !== null ? (string) $row['zip_code'] : null);
            $location->setPhone($row['phone'] !== null ? (string) $row['phone'] : null);
            $location->setFax($row['fax'] !== null ? (string) $row['fax'] : null);
            $location->setNote($row['note'] !== null ? (string) $row['note'] : null);
            $this->getLocationService()->saveLocation($location);
            $map[$key] = $location;
            $imported++;
        }

        return [$imported, $skipped, $map];
    }

    /**
     * @param PDO $pdo
     * @param OrangeHRMDatabaseImportParams $params
     * @param array<string, JobTitle> $jobTitleMap
     * @param array<string, EmploymentStatus> $empStatusMap
     * @param array<string, Location> $locationMap
     * @return array{0:int,1:int,2:array<int, string>}
     */
    private function importEmployees(
        PDO $pdo,
        OrangeHRMDatabaseImportParams $params,
        array $jobTitleMap,
        array $empStatusMap,
        array $locationMap
    ): array {
        $nationalityJoin = $this->tableExists($pdo, 'ohrm_nationality')
            ? 'LEFT JOIN ohrm_nationality n ON e.nation_code = n.id'
            : 'LEFT JOIN hs_hr_nationality n ON e.nation_code = n.nat_code';
        $nationalitySelect = $this->tableExists($pdo, 'ohrm_nationality')
            ? 'n.name AS nationality_name'
            : 'n.nat_name AS nationality_name';

        $sql = "SELECT e.emp_number, e.employee_id, e.emp_firstname, e.emp_middle_name, e.emp_lastname, e.emp_other_id,
                       e.emp_dri_lice_num, e.emp_dri_lice_exp_date, e.emp_gender, e.emp_marital_status,
                       e.emp_birthday, e.emp_street1, e.emp_street2, e.city_code, e.provinces_code,
                       e.emp_zipcode, e.coun_code, e.emp_hm_telephone, e.emp_mobile, e.emp_work_telephone,
                       e.emp_work_email, e.emp_oth_email, e.joined_date, e.termination_id,
                       jt.job_title AS job_title_name, es.name AS emp_status_name, {$nationalitySelect},
                       (
                           SELECT loc.name
                           FROM hs_hr_emp_locations el
                           INNER JOIN ohrm_location loc ON loc.id = el.location_id
                           WHERE el.emp_number = e.emp_number
                           ORDER BY loc.name ASC
                           LIMIT 1
                       ) AS location_name
                FROM hs_hr_employee e
                LEFT JOIN ohrm_job_title jt ON e.job_title_code = jt.id
                LEFT JOIN ohrm_employment_status es ON e.emp_status = es.id
                {$nationalityJoin}";
        if ($params->isActiveEmployeesOnly()) {
            $sql .= ' WHERE e.termination_id IS NULL';
        }
        $sql .= ' ORDER BY e.emp_number ASC';

        $rows = $pdo->query($sql)->fetchAll();
        $imported = 0;
        $skipped = 0;
        $failed = [];
        $employeeService = $this->getEmployeeService();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $firstName = trim((string) ($row['emp_firstname'] ?? ''));
            $lastName = trim((string) ($row['emp_lastname'] ?? ''));
            if ($firstName === '' || $lastName === '') {
                $failed[$rowNumber] = 'Missing first or last name';
                continue;
            }

            $employeeId = $this->nullIfEmpty($row['employee_id'] ?? null);
            $workEmail = $this->nullIfEmpty($row['emp_work_email'] ?? null);
            $otherEmail = $this->nullIfEmpty($row['emp_oth_email'] ?? null);

            if ($employeeId !== null && !$employeeService->isUniqueEmployeeId($employeeId)) {
                $skipped++;
                continue;
            }
            if ($workEmail !== null && !$employeeService->isUniqueEmail($workEmail)) {
                $skipped++;
                continue;
            }
            if ($otherEmail !== null && !$employeeService->isUniqueEmail($otherEmail)) {
                $skipped++;
                continue;
            }

            try {
                $employee = new Employee();
                $employee->setFirstName(mb_substr($firstName, 0, EmployeeService::FIRST_NAME_MAX_LENGTH));
                $middleName = trim((string) ($row['emp_middle_name'] ?? ''));
                $employee->setMiddleName(mb_substr($middleName, 0, EmployeeService::MIDDLE_NAME_MAX_LENGTH));
                $employee->setLastName(mb_substr($lastName, 0, EmployeeService::LAST_NAME_MAX_LENGTH));
                if ($employeeId !== null) {
                    $employee->setEmployeeId(mb_substr($employeeId, 0, EmployeeService::EMPLOYEE_ID_MAX_LENGTH));
                }
                if (($otherId = $this->nullIfEmpty($row['emp_other_id'] ?? null)) !== null) {
                    $employee->setOtherId(mb_substr($otherId, 0, 30));
                }
                if (($license = $this->nullIfEmpty($row['emp_dri_lice_num'] ?? null)) !== null) {
                    $employee->setDrivingLicenseNo(mb_substr($license, 0, 30));
                }
                $employee->setDrivingLicenseExpiredDate($this->toDateTime($row['emp_dri_lice_exp_date'] ?? null));
                $gender = $row['emp_gender'] ?? null;
                if ($gender == Employee::GENDER_MALE || $gender == Employee::GENDER_FEMALE || $gender == Employee::GENDER_OTHER) {
                    $employee->setGender((int) $gender);
                }
                $marital = $this->nullIfEmpty($row['emp_marital_status'] ?? null);
                if ($marital !== null) {
                    $employee->setMaritalStatus($marital);
                }
                $employee->setNationality($this->findNationalityByName($row['nationality_name'] ?? null));
                $employee->setBirthday($this->toDateTime($row['emp_birthday'] ?? null));
                if (($street1 = $this->nullIfEmpty($row['emp_street1'] ?? null)) !== null) {
                    $employee->setStreet1(mb_substr($street1, 0, 70));
                }
                if (($street2 = $this->nullIfEmpty($row['emp_street2'] ?? null)) !== null) {
                    $employee->setStreet2(mb_substr($street2, 0, 70));
                }
                if (($city = $this->nullIfEmpty($row['city_code'] ?? null)) !== null) {
                    $employee->setCity(mb_substr($city, 0, 70));
                }
                if (($province = $this->nullIfEmpty($row['provinces_code'] ?? null)) !== null) {
                    $employee->setProvince(mb_substr($province, 0, 70));
                }
                if (($zip = $this->nullIfEmpty($row['emp_zipcode'] ?? null)) !== null) {
                    $employee->setZipcode(mb_substr($zip, 0, 10));
                }
                $countryCode = $this->nullIfEmpty($row['coun_code'] ?? null);
                if ($countryCode !== null && $this->getCountryService()->getCountryByCountryCode($countryCode) instanceof Country) {
                    $employee->setCountry($countryCode);
                }
                if (($homePhone = $this->nullIfEmpty($row['emp_hm_telephone'] ?? null)) !== null) {
                    $employee->setHomeTelephone(mb_substr($homePhone, 0, 25));
                }
                if (($mobile = $this->nullIfEmpty($row['emp_mobile'] ?? null)) !== null) {
                    $employee->setMobile(mb_substr($mobile, 0, 25));
                }
                if (($workPhone = $this->nullIfEmpty($row['emp_work_telephone'] ?? null)) !== null) {
                    $employee->setWorkTelephone(mb_substr($workPhone, 0, 25));
                }
                if ($workEmail !== null) {
                    $employee->setWorkEmail(mb_substr($workEmail, 0, EmployeeService::WORK_EMAIL_MAX_LENGTH));
                }
                if ($otherEmail !== null) {
                    $employee->setOtherEmail(mb_substr($otherEmail, 0, EmployeeService::WORK_EMAIL_MAX_LENGTH));
                }
                $employee->setJoinedDate($this->toDateTime($row['joined_date'] ?? null));

                $jobTitleName = $this->nullIfEmpty($row['job_title_name'] ?? null);
                if ($jobTitleName !== null) {
                    $jobKey = mb_strtolower($jobTitleName);
                    if (isset($jobTitleMap[$jobKey])) {
                        $employee->setJobTitle($jobTitleMap[$jobKey]);
                    }
                }
                $statusName = $this->nullIfEmpty($row['emp_status_name'] ?? null);
                if ($statusName !== null) {
                    $statusKey = mb_strtolower($statusName);
                    if (isset($empStatusMap[$statusKey])) {
                        $employee->setEmpStatus($empStatusMap[$statusKey]);
                    }
                }
                $locationName = $this->nullIfEmpty($row['location_name'] ?? null);
                if ($locationName !== null) {
                    $locKey = mb_strtolower($locationName);
                    if (isset($locationMap[$locKey])) {
                        $employee->setLocations([$locationMap[$locKey]]);
                    }
                }

                $employeeService->saveEmployee($employee);
                $imported++;
            } catch (Throwable $e) {
                $this->getLogger()->error('Failed importing employee row ' . $rowNumber . ': ' . $e->getMessage());
                $failed[$rowNumber] = $e->getMessage();
            }
        }

        return [$imported, $skipped, $failed];
    }

    /**
     * @return array<string, JobTitle>
     */
    private function buildLocalJobTitleMap(): array
    {
        $map = [];
        foreach ($this->getJobTitleService()->getJobTitleList() as $jobTitle) {
            $map[mb_strtolower($jobTitle->getJobTitleName())] = $jobTitle;
        }
        return $map;
    }

    /**
     * @return array<string, EmploymentStatus>
     */
    private function buildLocalEmploymentStatusMap(): array
    {
        $map = [];
        $filter = new EmploymentStatusSearchFilterParams();
        $filter->setLimit(0);
        foreach ($this->getEmploymentStatusService()->searchEmploymentStatus($filter) as $status) {
            $map[mb_strtolower($status->getName())] = $status;
        }
        return $map;
    }

    /**
     * @return array<string, Location>
     */
    private function buildLocalLocationMap(): array
    {
        $map = [];
        $q = $this->createQueryBuilder(Location::class, 'l');
        foreach ($q->getQuery()->execute() as $location) {
            /** @var Location $location */
            $map[mb_strtolower($location->getName())] = $location;
        }
        return $map;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->query(sprintf('SHOW TABLES LIKE %s', $pdo->quote($table)));
        return $stmt !== false && $stmt->fetch() !== false;
    }

    private function findNationalityByName(?string $name): ?Nationality
    {
        $name = $this->nullIfEmpty($name);
        if ($name === null) {
            return null;
        }
        return $this->getRepository(Nationality::class)->findOneBy(['name' => $name]);
    }

    private function toDateTime(mixed $value): ?DateTime
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }
        try {
            return new DateTime((string) $value);
        } catch (Exception) {
            return null;
        }
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    public function getJobTitleService(): JobTitleService
    {
        return $this->jobTitleService ??= new JobTitleService();
    }

    public function setJobTitleService(JobTitleService $jobTitleService): void
    {
        $this->jobTitleService = $jobTitleService;
    }

    public function getEmploymentStatusService(): EmploymentStatusService
    {
        return $this->employmentStatusService ??= new EmploymentStatusService();
    }

    public function setEmploymentStatusService(EmploymentStatusService $employmentStatusService): void
    {
        $this->employmentStatusService = $employmentStatusService;
    }

    public function getJobCategoryService(): JobCategoryService
    {
        return $this->jobCategoryService ??= new JobCategoryService();
    }

    public function setJobCategoryService(JobCategoryService $jobCategoryService): void
    {
        $this->jobCategoryService = $jobCategoryService;
    }

    public function getLocationService(): LocationService
    {
        return $this->locationService ??= new LocationService();
    }

    public function setLocationService(LocationService $locationService): void
    {
        $this->locationService = $locationService;
    }

    public function getCountryService(): CountryService
    {
        return $this->getContainer()->get(Services::COUNTRY_SERVICE);
    }
}
