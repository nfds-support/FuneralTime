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
    <oxd-table-filter :filter-title="$t('time.ufcw_monthly_remittance')">
      <oxd-form @submit-valid="onGenerate">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.month"
                :label="$t('general.month')"
                placeholder="YYYY-MM"
                :rules="rules.month"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.preparedBy"
                :label="$t('time.prepared_by')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.updateInitiationBalances"
                type="checkbox"
                :option-label="$t('time.update_initiation_balances')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('time.generate_ufcw_remittance')"
          />
          <oxd-button
            v-if="rows.length"
            display-type="ghost"
            class="orangehrm-left-space"
            :label="$t('time.download_xlsx')"
            :disabled="isBusy"
            @click="onDownload"
          />
          <oxd-button
            v-if="rows.length"
            display-type="ghost"
            class="orangehrm-left-space"
            :label="$t('time.email_ufcw_remittance')"
            :disabled="isBusy"
            @click="onEmail"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div v-if="meta" class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="p">
          {{ meta.reportMonthLabel }} · {{ meta.status }} ·
          {{ $t('time.remittance_due') }}: {{ meta.remittanceDueDate }}
        </oxd-text>
        <oxd-text tag="p">
          {{ $t('time.regular_union_dues') }}:
          ${{ formatMoney(meta.totals?.unionDues) }} ·
          {{ $t('time.initiation_fees') }}:
          ${{ formatMoney(meta.totals?.initiationFees) }} ·
          {{ $t('time.total_remittance') }}:
          ${{ formatMoney(meta.totals?.remittance) }}
        </oxd-text>
      </div>
      <div class="orangehrm-container">
        <div
          v-for="row in rows"
          :key="row.empNumber"
          class="orangehrm-ufcw-employee-row"
        >
          <oxd-text tag="p" class="orangehrm-ufcw-employee-name">
            {{ row.fullName }}
            <span v-if="row.reviewFlags?.length" class="orangehrm-ufcw-flag">
              ({{ row.reviewFlags.join('; ') }})
            </span>
          </oxd-text>
          <oxd-form-row>
            <oxd-grid :cols="4" class="orangehrm-full-width-grid">
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.telephone"
                  label="Telephone"
                  :class="{'orangehrm-ufcw-missing': isMissing(row, 'telephone')}"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.email"
                  label="Email"
                  :class="{'orangehrm-ufcw-missing': isMissing(row, 'email')}"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.rateOfPay"
                  label="Rate of pay"
                  :class="{'orangehrm-ufcw-missing': isMissing(row, 'rateOfPay')}"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.classification"
                  label="Classification"
                  :class="{
                    'orangehrm-ufcw-missing': isMissing(row, 'classification'),
                  }"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.ftPtDesignation"
                  type="select"
                  label="FT/PT"
                  :options="ftPtOptions"
                  :show-empty-selector="false"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.unionDuesDeducted"
                  label="Union dues"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.initiationFeesDeducted"
                  label="Initiation fees"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="row.reasonNoDeduction"
                  :label="$t('time.reason_notes')"
                  :class="{'orangehrm-ufcw-danger': row.needsNoDeductionReason}"
                />
              </oxd-grid-item>
            </oxd-grid>
          </oxd-form-row>
          <oxd-divider />
        </div>
        <oxd-text v-if="!rows.length && generated" tag="p">
          No bargaining-unit employees found for this month. Assign the
          configured UFCW membership under PIM → Memberships.
        </oxd-text>
      </div>
    </div>
  </div>
</template>

<script>
import {ref} from 'vue';
import {required} from '@/core/util/validation/rules';
import {APIService} from '@/core/util/services/api.service';
import useToast from '@/core/util/composable/useToast';
import usei18n from '@/core/util/composable/usei18n';

export default {
  name: 'UfcwRemittanceReport',
  setup() {
    const {$t} = usei18n();
    const {success, error} = useToast();
    const http = new APIService(
      window.appGlobal.baseUrl,
      'api/v2/time/ufcw-remittance/report',
    );
    const isBusy = ref(false);
    const generated = ref(false);
    const rows = ref([]);
    const meta = ref(null);
    const filters = ref({
      month: '',
      preparedBy: '',
      updateInitiationBalances: false,
    });
    const rules = {
      month: [
        required,
        (v) => /^\d{4}-\d{2}$/.test(String(v || '')) || 'YYYY-MM',
      ],
    };
    const ftPtOptions = [
      {id: 'Full-time', label: 'Full-time'},
      {id: 'Part-time', label: 'Part-time'},
      {id: 'Other / N/A', label: 'Other / N/A'},
    ];

    const formatMoney = (value) =>
      Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    const isMissing = (row, field) =>
      Array.isArray(row.missingRequiredFields) &&
      row.missingRequiredFields.includes(field);

    const normalizeRows = (data) =>
      (data || []).map((row) => ({
        ...row,
        rateOfPay: row.rateOfPay === null || row.rateOfPay === undefined ? '' : String(row.rateOfPay),
        unionDuesDeducted: String(row.unionDuesDeducted ?? 0),
        initiationFeesDeducted: String(row.initiationFeesDeducted ?? 0),
        ftPtDesignation:
          typeof row.ftPtDesignation === 'string'
            ? {id: row.ftPtDesignation, label: row.ftPtDesignation}
            : row.ftPtDesignation,
      }));

    const payloadEmployees = () =>
      rows.value.map((row) => ({
        empNumber: row.empNumber,
        telephone: row.telephone,
        email: row.email,
        rateOfPay:
          row.rateOfPay === '' || row.rateOfPay === null
            ? null
            : Number(row.rateOfPay),
        classification: row.classification,
        ftPtDesignation: row.ftPtDesignation?.id || row.ftPtDesignation || '',
        unionDuesDeducted: Number(row.unionDuesDeducted || 0),
        initiationFeesDeducted: Number(row.initiationFeesDeducted || 0),
        reasonNoDeduction: row.reasonNoDeduction || '',
        notes: row.notes || '',
        payrollPeriods: row.payrollPeriods,
        weekEndingDates: row.weekEndingDates,
      }));

    const onGenerate = async () => {
      isBusy.value = true;
      generated.value = true;
      try {
        const {data} = await http.getAll({
          month: filters.value.month,
          preparedBy: filters.value.preparedBy || undefined,
        });
        rows.value = normalizeRows(data?.data || []);
        meta.value = data?.meta || null;
      } catch (e) {
        error({
          title: $t('general.error'),
          message: 'Unable to generate remittance preview',
        });
      } finally {
        isBusy.value = false;
      }
    };

    const onDownload = async () => {
      isBusy.value = true;
      try {
        const response = await http.http.post(
          'time/downloadUfcwRemittanceReport',
          {
            month: filters.value.month,
            preparedBy: filters.value.preparedBy,
            employees: payloadEmployees(),
          },
          {responseType: 'blob'},
        );
        const blob = new Blob([response.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `UFCW_Remittance_${filters.value.month}.xlsx`;
        link.click();
        window.URL.revokeObjectURL(url);
      } catch (e) {
        error({
          title: $t('general.error'),
          message: 'Unable to download workbook',
        });
      } finally {
        isBusy.value = false;
      }
    };

    const onEmail = async () => {
      isBusy.value = true;
      try {
        const {data} = await http.create({
          month: filters.value.month,
          preparedBy: filters.value.preparedBy,
          sendEmail: true,
          updateInitiationBalances: !!filters.value.updateInitiationBalances,
          employees: payloadEmployees(),
        });
        if (data?.data?.sent) {
          success({
            toastMessage: `Emailed to ${(data.data.recipients || []).join(', ')}`,
          });
        } else {
          error({
            title: $t('general.error'),
            message:
              'Email was not sent. Check Email Configuration and recipient addresses.',
          });
        }
      } catch (e) {
        error({
          title: $t('general.error'),
          message: 'Unable to email remittance report',
        });
      } finally {
        isBusy.value = false;
      }
    };

    return {
      filters,
      rules,
      rows,
      meta,
      isBusy,
      generated,
      ftPtOptions,
      formatMoney,
      isMissing,
      onGenerate,
      onDownload,
      onEmail,
    };
  },
};
</script>

<style scoped>
.orangehrm-ufcw-employee-name {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.orangehrm-ufcw-flag {
  color: #b36b00;
  font-weight: 400;
}
.orangehrm-ufcw-missing :deep(input),
.orangehrm-ufcw-missing :deep(.oxd-input) {
  background-color: #ffff99;
}
.orangehrm-ufcw-danger :deep(input),
.orangehrm-ufcw-danger :deep(.oxd-input) {
  background-color: #ffcccc;
}
.orangehrm-ufcw-employee-row {
  padding: 0.5rem 1rem;
}
</style>
