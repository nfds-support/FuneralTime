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
    <oxd-table-filter :filter-title="$t('leave.banked_time_payout')">
      <oxd-form @submit-valid="onPayout">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <employee-autocomplete
                v-model="form.employee"
                :rules="rules.employee"
                required
                :params="{includeEmployees: 'currentAndPast'}"
                @update:model-value="onEmployeeChange"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                :model-value="bankedHoursDisplay"
                :label="$t('leave.banked_hours_available')"
                disabled
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.hours"
                :label="$t('leave.hours_to_pay_out')"
                :rules="rules.hours"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.note"
                :label="$t('general.notes')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-row>
          <oxd-text tag="p" class="orangehrm-form-hint">
            {{ $t('leave.banked_time_payout_hint') }}
          </oxd-text>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('leave.pay_out_banked_time')"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div class="orangehrm-paper-container">
      <div class="orangehrm-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('leave.recent_banked_time_payouts') }}
        </oxd-text>
        <oxd-divider />
        <oxd-card-table
          :headers="headers"
          :items="items"
          :clickable="false"
          :loading="isLoading"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
    </div>
  </div>
</template>

<script>
import {ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import usei18n from '@/core/util/composable/usei18n';
import useToast from '@/core/util/composable/useToast';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';
import {
  required,
  digitsOnlyWithDecimalPoint,
} from '@/core/util/validation/rules';

export default {
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  setup() {
    const {$t} = usei18n();
    const {success, unexpectedError} = useToast();
    const isLoading = ref(false);
    const items = ref([]);
    const bankedHours = ref(null);
    const form = ref({employee: null, hours: '', note: ''});

    const rules = {
      employee: [required],
      hours: [required, digitsOnlyWithDecimalPoint],
    };

    const headers = [
      {name: 'employeeName', title: $t('general.employee'), style: {flex: 1}},
      {
        name: 'hours',
        title: $t('leave.hours_deducted'),
        style: {flex: 1},
      },
      {
        name: 'balanceAfterHours',
        title: $t('leave.balance_after_hours'),
        style: {flex: 1},
      },
      {name: 'note', title: $t('general.notes'), style: {flex: 1}},
      {name: 'createdBy', title: $t('leave.paid_out_by'), style: {flex: 1}},
      {name: 'createdAt', title: $t('general.date'), style: {flex: 1}},
    ];

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/leave/banked-time-payouts',
    );

    const bankedHoursDisplay = ref('—');
    const syncBankedDisplay = () => {
      bankedHoursDisplay.value =
        bankedHours.value === null
          ? '—'
          : Number(bankedHours.value).toFixed(2);
    };

    const loadPayouts = () => {
      isLoading.value = true;
      const params = {};
      if (form.value.employee?.id) {
        params.empNumber = form.value.employee.id;
      }
      http
        .getAll(params)
        .then(({data}) => {
          items.value = data.data || [];
          if (params.empNumber) {
            bankedHours.value =
              data.meta?.bankedHours !== undefined
                ? Number(data.meta.bankedHours)
                : null;
            syncBankedDisplay();
          }
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    const onEmployeeChange = () => {
      bankedHours.value = null;
      syncBankedDisplay();
      loadPayouts();
    };

    const onPayout = () => {
      if (!form.value.employee?.id) {
        return;
      }
      isLoading.value = true;
      http
        .create({
          empNumber: form.value.employee.id,
          hours: parseFloat(form.value.hours),
          note: form.value.note || undefined,
        })
        .then(() => {
          success({
            title: $t('general.success'),
            message: $t('leave.banked_time_payout_success'),
          });
          form.value.hours = '';
          form.value.note = '';
          loadPayouts();
        })
        .catch(() => unexpectedError())
        .finally(() => {
          isLoading.value = false;
        });
    };

    loadPayouts();

    return {
      isLoading,
      items,
      form,
      rules,
      headers,
      bankedHoursDisplay,
      onEmployeeChange,
      onPayout,
    };
  },
};
</script>
