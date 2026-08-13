<!--
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
 -->

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text class="orangehrm-main-title">
        {{ $t('admin.import_from_orangehrm') }}
      </oxd-text>
      <oxd-divider />

      <div class="orangehrm-information-card-container">
        <oxd-text class="orangehrm-sub-title">
          {{ $t('general.note') }}:
        </oxd-text>
        <ul>
          <li>
            <oxd-text class="orangehrm-information-card-text">
              {{ $t('admin.import_from_orangehrm_intro') }}
            </oxd-text>
          </li>
          <li>
            <oxd-text class="orangehrm-information-card-text">
              {{ $t('admin.import_from_orangehrm_csv_hint') }}
            </oxd-text>
          </li>
          <li>
            <oxd-text class="orangehrm-information-card-text">
              {{ $t('admin.import_from_orangehrm_db_hint') }}
            </oxd-text>
          </li>
        </ul>
      </div>
      <br />

      <oxd-tab-container v-model="tabSelector">
        <oxd-tab-panel key="csv" :name="$t('admin.import_csv_tab')">
          <oxd-form ref="csvFormRef" :loading="csvLoading" @submit-valid="onCsvImport">
            <oxd-form-row>
              <oxd-grid :cols="3" class="orangehrm-full-width-grid">
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="csvAttachment.attachment"
                    type="file"
                    :rules="csvRules.attachment"
                    :label="$t('general.select_file')"
                    :button-label="$t('general.browse')"
                    :placeholder="$t('general.no_file_selected')"
                    required
                  />
                  <oxd-text class="orangehrm-input-hint" tag="p">
                    {{
                      $t('general.accepts_up_to_n_mb', {count: formattedFileSize})
                    }}
                  </oxd-text>
                  <oxd-text class="orangehrm-input-hint" tag="p">
                    {{ $t('pim.sample_csv_file') }}:
                    <a
                      href="#"
                      class="download-link"
                      @click.prevent="onClickDownloadSample"
                    >
                      {{ $t('general.download') }}
                    </a>
                  </oxd-text>
                </oxd-grid-item>
              </oxd-grid>
            </oxd-form-row>
            <oxd-divider />
            <oxd-form-actions>
              <required-text />
              <submit-button :label="$t('general.upload')" />
            </oxd-form-actions>
          </oxd-form>
        </oxd-tab-panel>

        <oxd-tab-panel key="database" :name="$t('admin.import_database_tab')">
          <oxd-form
            ref="dbFormRef"
            :loading="dbLoading"
            @submit-valid="onDatabaseImport(false)"
          >
            <oxd-text tag="p" class="orangehrm-subtitle">
              {{ $t('admin.source_database_settings') }}
            </oxd-text>
            <oxd-form-row>
              <oxd-grid :cols="3" class="orangehrm-full-width-grid">
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="dbForm.host"
                    :label="$t('admin.host')"
                    :rules="dbRules.host"
                    required
                  />
                </oxd-grid-item>
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="dbForm.port"
                    :label="$t('admin.port')"
                    :rules="dbRules.port"
                    required
                  />
                </oxd-grid-item>
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="dbForm.database"
                    :label="$t('admin.database_name')"
                    :rules="dbRules.database"
                    required
                  />
                </oxd-grid-item>
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="dbForm.username"
                    :label="$t('general.username')"
                    :rules="dbRules.username"
                    required
                  />
                </oxd-grid-item>
                <oxd-grid-item>
                  <oxd-input-field
                    v-model="dbForm.password"
                    type="password"
                    :label="$t('general.password')"
                  />
                </oxd-grid-item>
              </oxd-grid>
            </oxd-form-row>

            <oxd-divider class="orangehrm-form-divider" />
            <oxd-text tag="p" class="orangehrm-subtitle">
              {{ $t('admin.import_options') }}
            </oxd-text>
            <oxd-form-row>
              <oxd-grid :cols="2" class="orangehrm-full-width-grid">
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.import_job_titles') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.importJobTitles" />
                </oxd-grid-item>
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.import_employment_statuses') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.importEmploymentStatuses" />
                </oxd-grid-item>
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.import_job_categories') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.importJobCategories" />
                </oxd-grid-item>
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.import_locations') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.importLocations" />
                </oxd-grid-item>
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.import_employees') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.importEmployees" />
                </oxd-grid-item>
                <oxd-grid-item class="orangehrm-import-switch">
                  <oxd-text tag="p" class="orangehrm-import-switch-text">
                    {{ $t('admin.active_employees_only') }}
                  </oxd-text>
                  <oxd-switch-input v-model="dbForm.activeEmployeesOnly" />
                </oxd-grid-item>
              </oxd-grid>
            </oxd-form-row>

            <oxd-divider />
            <oxd-form-actions>
              <required-text />
              <oxd-button
                display-type="ghost"
                :label="$t('admin.preview_import')"
                :disabled="dbLoading"
                @click.prevent="onDatabaseImport(true)"
              />
              <submit-button :label="$t('admin.run_import')" />
            </oxd-form-actions>
          </oxd-form>
        </oxd-tab-panel>
      </oxd-tab-container>
    </div>

    <employee-data-import-modal
      v-if="csvModalState"
      :data="csvModalState"
      @close="csvModalState = null"
    ></employee-data-import-modal>

    <oxd-dialog
      v-if="dbResult"
      :persistent="true"
      @update:show="onCloseDbResult"
    >
      <div class="orangehrm-modal-header">
        <oxd-text type="card-title">
          {{
            dbResult.dryRun
              ? $t('admin.import_preview_title')
              : $t('admin.import_complete_title')
          }}
        </oxd-text>
      </div>
      <oxd-divider />
      <div class="orangehrm-import-result">
        <oxd-text tag="p">
          {{ $t('admin.preview_counts') }}:
          {{ formatCounts(dbResult.preview) }}
        </oxd-text>
        <template v-if="!dbResult.dryRun">
          <oxd-text tag="p">
            {{ $t('admin.imported_counts') }}:
            {{ formatCounts(dbResult.imported) }}
          </oxd-text>
          <oxd-text tag="p">
            {{ $t('admin.skipped_counts') }}:
            {{ formatCounts(dbResult.skipped) }}
          </oxd-text>
        </template>
      </div>
      <oxd-divider />
      <oxd-form-actions>
        <oxd-button
          display-type="secondary"
          :label="$t('general.ok')"
          @click="onCloseDbResult"
        />
      </oxd-form-actions>
    </oxd-dialog>
  </div>
</template>

<script>
import {
  required,
  maxFileSize,
  validFileTypes,
  digitsOnly,
  shouldNotExceedCharLength,
} from '@/core/util/validation/rules';
import useForm from '@ohrm/core/util/composable/useForm';
import {APIService} from '@/core/util/services/api.service';
import EmployeeDataImportModal from '@/orangehrmPimPlugin/components/EmployeeDataImportModal';
import {OxdTabPanel, OxdTabContainer, OxdDialog, OxdButton, OxdSwitchInput} from '@ohrm/oxd';
import RequiredText from '@/core/components/labels/RequiredText.vue';
import SubmitButton from '@/core/components/buttons/SubmitButton.vue';
import useToast from '@/core/util/composable/useToast';

const csvAttachmentModel = {
  attachment: null,
};

const dbFormModel = {
  host: '127.0.0.1',
  port: '3306',
  database: '',
  username: '',
  password: '',
  importJobTitles: true,
  importEmploymentStatuses: true,
  importJobCategories: true,
  importLocations: true,
  importEmployees: true,
  activeEmployeesOnly: true,
};

export default {
  components: {
    RequiredText,
    SubmitButton,
    'employee-data-import-modal': EmployeeDataImportModal,
    'oxd-tab-panel': OxdTabPanel,
    'oxd-tab-container': OxdTabContainer,
    'oxd-dialog': OxdDialog,
    'oxd-button': OxdButton,
    'oxd-switch-input': OxdSwitchInput,
  },
  props: {
    allowedFileTypes: {
      type: Array,
      required: true,
    },
    maxFileSize: {
      type: Number,
      required: true,
    },
  },
  setup() {
    const csvHttp = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/admin/orangehrm-import/csv`,
    );
    const dbHttp = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/admin/orangehrm-import/database`,
    );
    const {formRef: csvFormRef, reset: resetCsv} = useForm();
    const {formRef: dbFormRef} = useForm();
    const {success, error} = useToast();
    return {
      csvHttp,
      dbHttp,
      csvFormRef,
      dbFormRef,
      resetCsv,
      success,
      error,
    };
  },
  data() {
    return {
      tabSelector: 0,
      csvLoading: false,
      dbLoading: false,
      csvAttachment: {...csvAttachmentModel},
      dbForm: {...dbFormModel},
      csvRules: {
        attachment: [
          required,
          maxFileSize(this.maxFileSize),
          validFileTypes(this.allowedFileTypes),
        ],
      },
      dbRules: {
        host: [required, shouldNotExceedCharLength(255)],
        port: [required, digitsOnly],
        database: [required, shouldNotExceedCharLength(64)],
        username: [required, shouldNotExceedCharLength(64)],
      },
      csvModalState: null,
      dbResult: null,
    };
  },
  computed: {
    formattedFileSize() {
      return Math.round((this.maxFileSize / (1024 * 1024)) * 100) / 100;
    },
  },
  methods: {
    onCsvImport() {
      this.csvLoading = true;
      this.csvHttp
        .create({
          ...this.csvAttachment,
        })
        .then((response) => {
          this.csvModalState = response.data.meta;
        })
        .catch(() => {
          this.error({
            title: this.$t('general.error'),
            message: this.$t('admin.import_failed'),
          });
        })
        .finally(() => {
          this.resetCsv();
          this.csvLoading = false;
        });
    },
    onClickDownloadSample() {
      window.open(
        `${window.appGlobal.baseUrl}/pim/sampleCsvDownload`,
        '_blank',
      );
    },
    onDatabaseImport(dryRun) {
      this.dbLoading = true;
      const payload = {
        host: this.dbForm.host,
        port: Number(this.dbForm.port),
        database: this.dbForm.database,
        username: this.dbForm.username,
        password: this.dbForm.password,
        dryRun,
        activeEmployeesOnly: this.dbForm.activeEmployeesOnly,
        importJobTitles: this.dbForm.importJobTitles,
        importEmploymentStatuses: this.dbForm.importEmploymentStatuses,
        importJobCategories: this.dbForm.importJobCategories,
        importLocations: this.dbForm.importLocations,
        importEmployees: this.dbForm.importEmployees,
      };
      this.dbHttp
        .create(payload)
        .then((response) => {
          this.dbResult = response.data.data;
          if (!dryRun) {
            this.success({
              title: this.$t('general.success'),
              message: this.$t('admin.import_complete_title'),
            });
          }
        })
        .catch((err) => {
          const message =
            err?.response?.data?.error?.message ||
            this.$t('admin.import_failed');
          this.error({
            title: this.$t('general.error'),
            message,
          });
        })
        .finally(() => {
          this.dbLoading = false;
        });
    },
    formatCounts(counts) {
      if (!counts) {
        return '';
      }
      return Object.entries(counts)
        .map(([key, value]) => `${key}: ${value}`)
        .join(', ');
    },
    onCloseDbResult() {
      this.dbResult = null;
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-information-card-container {
  background-color: $oxd-interface-gray-lighten-2-color;
  border-radius: 1.2rem;
  padding: 1.2rem;
}
.orangehrm-information-card-text {
  font-size: $oxd-input-control-font-size;
  color: $oxd-input-control-font-color;
  font-weight: $oxd-input-control-font-weight;
  & .download-link {
    color: $oxd-primary-one-color;
  }
}
.orangehrm-subtitle {
  font-weight: 700;
  margin-bottom: 0.75rem;
}
.orangehrm-import-switch {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.35rem 0;
}
.orangehrm-import-switch-text {
  margin: 0;
}
.orangehrm-import-result {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0 0.25rem 0.5rem;
}
</style>
