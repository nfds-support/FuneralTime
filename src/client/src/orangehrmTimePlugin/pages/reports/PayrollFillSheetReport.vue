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
    <oxd-table-filter :filter-title="$t('time.payroll_fill_sheet_report')">
      <oxd-form @submit-valid="onGenerate">
        <oxd-form-row>
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.period"
                type="select"
                :label="$t('time.payroll_period')"
                :options="periodOptions"
                :rules="rules.period"
                required
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
            :label="$t('general.view')"
          />
          <oxd-button
            v-if="rows.length"
            display-type="ghost"
            class="orangehrm-left-space"
            :label="$t('general.download')"
            @click="exportCsv"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div class="orangehrm-paper-container">
      <div class="orangehrm-container">
        <oxd-card-table
          :headers="headers"
          :items="rows"
          :clickable="false"
          :loading="isLoading"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
    </div>
  </div>
</template>

<script>
import {onBeforeMount, ref} from 'vue';
import {required} from '@/core/util/validation/rules';
import {APIService} from '@/core/util/services/api.service';
import usei18n from '@/core/util/composable/usei18n';

export default {
  setup() {
    const {$t} = usei18n();
    const isLoading = ref(false);
    const rows = ref([]);
    const periodOptions = ref([]);
    const filters = ref({period: null});
    const rules = {period: [required]};

    const headers = [
      {name: 'employeeId', title: $t('general.employee_id'), style: {flex: 1}},
      {name: 'lastName', title: $t('general.last_name'), style: {flex: 1}},
      {name: 'firstName', title: $t('general.first_name'), style: {flex: 1}},
      {name: 'group', title: $t('general.sub_unit'), style: {flex: 1}},
      {name: 'regularW1', title: 'Reg W1', style: {flex: 1}},
      {name: 'regularW2', title: 'Reg W2', style: {flex: 1}},
      {name: 'otW1', title: 'OT W1', style: {flex: 1}},
      {name: 'otW2', title: 'OT W2', style: {flex: 1}},
      {name: 'onCallW1', title: 'On-Call W1', style: {flex: 1}},
      {name: 'onCallW2', title: 'On-Call W2', style: {flex: 1}},
      {name: 'sickHours', title: 'Sick', style: {flex: 1}},
      {name: 'vacationW1', title: 'Vac W1', style: {flex: 1}},
      {name: 'vacationW2', title: 'Vac W2', style: {flex: 1}},
      {name: 'bankedW1', title: 'Banked W1', style: {flex: 1}},
      {name: 'bankedW2', title: 'Banked W2', style: {flex: 1}},
    ];

    const periodsHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/payroll-periods',
    );
    const reportHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/reports/payroll-fill-sheet',
    );

    onBeforeMount(() => {
      periodsHttp.getAll({limit: 0}).then(({data}) => {
        periodOptions.value = (data.data || []).map((p) => ({
          id: p.id,
          label: p.label || `P${p.periodNumber}: ${p.startDate} – ${p.endDate}`,
        }));
      });
    });

    const onGenerate = () => {
      if (!filters.value.period?.id) return;
      isLoading.value = true;
      reportHttp
        .getAll({periodId: filters.value.period.id})
        .then(({data}) => {
          rows.value = data.data || [];
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    const exportCsv = () => {
      const cols = [
        'employeeId',
        'lastName',
        'firstName',
        'group',
        'regularW1',
        'regularW2',
        'otW1',
        'otW2',
        'onCallW1',
        'onCallW2',
        'sickHours',
        'vacationW1',
        'vacationW2',
        'bankedW1',
        'bankedW2',
      ];
      const lines = [cols.join(',')];
      rows.value.forEach((row) => {
        lines.push(cols.map((c) => JSON.stringify(row[c] ?? '')).join(','));
      });
      const blob = new Blob([lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'payroll-fill-sheet.csv';
      link.click();
      URL.revokeObjectURL(url);
    };

    return {
      isLoading,
      rows,
      headers,
      filters,
      rules,
      periodOptions,
      onGenerate,
      exportCsv,
    };
  },
};
</script>
