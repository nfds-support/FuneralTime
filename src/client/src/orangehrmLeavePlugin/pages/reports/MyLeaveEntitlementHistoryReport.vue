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
    <oxd-table-filter :filter-title="$t('leave.my_leave_entitlement_history')">
      <oxd-form @submit-valid="onSearch" @reset="onReset">
        <oxd-form-row>
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <leave-type-dropdown
                v-model="filters.leaveType"
                :eligible-only="false"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.transactionType"
                type="select"
                :label="$t('leave.transaction_type')"
                :options="transactionTypeOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="filters.fromDate"
                :label="$t('general.from')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input v-model="filters.toDate" :label="$t('general.to')" />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <oxd-button
            type="reset"
            display-type="ghost"
            :label="$t('general.reset')"
          />
          <oxd-button
            type="submit"
            display-type="secondary"
            class="orangehrm-left-space"
            :label="$t('general.search')"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div class="orangehrm-paper-container">
      <div class="orangehrm-container">
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
import LeaveTypeDropdown from '@/orangehrmLeavePlugin/components/LeaveTypeDropdown';

const defaultFilters = {
  leaveType: null,
  transactionType: null,
  fromDate: null,
  toDate: null,
};

export default {
  components: {
    'leave-type-dropdown': LeaveTypeDropdown,
  },
  setup() {
    const {$t} = usei18n();
    const isLoading = ref(false);
    const items = ref([]);
    const filters = ref({...defaultFilters});
    const transactionTypeOptions = [
      {id: 'addition', label: 'Addition'},
      {id: 'deduction', label: 'Deduction'},
      {id: 'correction', label: 'Correction'},
      {id: 'usage', label: 'Usage'},
    ];
    const headers = [
      {
        name: 'leaveType',
        title: $t('leave.leave_type'),
        style: {flex: 1},
      },
      {
        name: 'transactionType',
        title: $t('leave.transaction_type'),
        style: {flex: 1},
      },
      {name: 'days', title: $t('general.days'), style: {flex: 1}},
      {name: 'balanceAfter', title: $t('leave.balance_after'), style: {flex: 1}},
      {name: 'note', title: $t('general.notes'), style: {flex: 1}},
      {name: 'createdAt', title: $t('general.date'), style: {flex: 1}},
    ];

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/leave/entitlement-transactions',
    );

    const buildParams = () => {
      const params = {limit: 100};
      if (filters.value.leaveType?.id) {
        params.leaveTypeId = filters.value.leaveType.id;
      }
      if (filters.value.transactionType?.id) {
        params.transactionType = filters.value.transactionType.id;
      }
      if (filters.value.fromDate) {
        params.fromDate = filters.value.fromDate;
      }
      if (filters.value.toDate) {
        params.toDate = filters.value.toDate;
      }
      return params;
    };

    const onSearch = () => {
      isLoading.value = true;
      http
        .getAll(buildParams())
        .then(({data}) => {
          items.value = (data.data || []).map((row) => ({
            ...row,
            leaveType: row.leaveType?.name,
          }));
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    const onReset = () => {
      filters.value = {...defaultFilters};
      onSearch();
    };

    onSearch();

    return {
      isLoading,
      items,
      headers,
      filters,
      transactionTypeOptions,
      onSearch,
      onReset,
    };
  },
};
</script>
