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
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('time.employee_fuel_for_banked_time') }}
        </oxd-text>
      </div>
      <oxd-form @submit-valid="onSearch">
        <div class="orangehrm-horizontal-padding orangehrm-vertical-padding">
          <oxd-form-row>
            <oxd-grid :cols="3" class="orangehrm-full-width-grid">
              <oxd-grid-item>
                <employee-autocomplete
                  v-model="filters.employee"
                  :params="{includeEmployees: 'onlyCurrent'}"
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="filters.status"
                  type="select"
                  :label="$t('general.status')"
                  :options="statusOptions"
                  clear
                />
              </oxd-grid-item>
            </oxd-grid>
          </oxd-form-row>
        </div>
        <div
          class="orangehrm-horizontal-padding orangehrm-vertical-padding orangehrm-clearform-margin"
        >
          <oxd-divider />
          <oxd-form-actions>
            <oxd-button
              display-type="ghost"
              :label="$t('general.reset')"
              type="reset"
              @click="onReset"
            />
            <oxd-button
              class="orangehrm-left-space"
              display-type="secondary"
              :label="$t('general.search')"
              type="submit"
            />
          </oxd-form-actions>
        </div>
      </oxd-form>
      <table-header
        :selected="0"
        :total="total"
        :loading="isLoading"
      ></table-header>
      <div class="orangehrm-container">
        <oxd-card-table
          :headers="headers"
          :items="items?.data"
          :clickable="false"
          :loading="isLoading"
          :cell-renderer="cellRenderer"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
      <div class="orangehrm-bottom-container">
        <oxd-pagination
          v-if="showPaginator"
          v-model:current="currentPage"
          :length="pages"
        />
      </div>
    </div>
  </div>
</template>

<script>
import {computed, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import usei18n from '@/core/util/composable/usei18n';
import useToast from '@/core/util/composable/useToast';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';

export default {
  name: 'EmployeeFuelBankedTime',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  setup() {
    const {$t} = usei18n();
    const {toastSuccess, toastError} = useToast();
    const filters = ref({
      employee: null,
      status: {id: 'PENDING', label: 'PENDING'},
    });

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/employees/fuel-banked-time/requests',
    );
    const actionHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/fuel-banked-time/requests',
    );

    const serializedFilters = computed(() => ({
      empNumber: filters.value.employee?.id,
      status: filters.value.status?.id,
    }));

    const performAction = async (item, action) => {
      try {
        await actionHttp.request({
          method: 'PUT',
          url: `/api/v2/time/fuel-banked-time/requests/${item.id}/action`,
          data: {action},
        });
        toastSuccess({
          title: $t('general.success'),
          message: $t('general.successfully_updated'),
        });
        await execQuery();
      } catch (error) {
        toastError({
          title: $t('general.error'),
          message:
            error?.response?.data?.error?.message ||
            $t('general.unexpected_error'),
        });
      }
    };

    const normalizer = (data) =>
      data.map((item) => ({
        id: item.id,
        employee: `${item.employee.firstName} ${item.employee.lastName}`,
        amount: Number(item.amount).toFixed(2),
        hourlyRate: Number(item.hourlyRate).toFixed(2),
        hours: Number(item.hours).toFixed(2),
        status: item.status,
        createdAt: item.createdAt,
        canAction: item.status === 'PENDING',
      }));

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      query: serializedFilters,
      normalizer,
    });

    const statusOptions = [
      {id: 'PENDING', label: 'PENDING'},
      {id: 'APPROVED', label: 'APPROVED'},
      {id: 'REJECTED', label: 'REJECTED'},
      {id: 'CANCELLED', label: 'CANCELLED'},
    ];

    const headers = computed(() => [
      {
        name: 'employee',
        slot: 'title',
        title: $t('general.employee_name'),
        style: {flex: 1},
      },
      {name: 'createdAt', title: $t('general.date'), style: {flex: 1}},
      {name: 'amount', title: $t('time.fuel_amount'), style: {flex: 1}},
      {name: 'hours', title: $t('time.hours_to_deduct'), style: {flex: 1}},
      {name: 'status', title: $t('general.status'), style: {flex: 1}},
      {
        name: 'actions',
        slot: 'action',
        title: $t('general.actions'),
        style: {flex: 1},
        cellType: 'oxd-table-cell-actions',
        cellConfig: {},
      },
    ]);

    const cellRenderer = (...[, , , row]) => {
      const cellConfig = {};
      if (row.canAction) {
        cellConfig.approve = {
          onClick: (item) => performAction(item, 'APPROVE'),
          component: 'oxd-button',
          props: {
            label: $t('general.approve'),
            displayType: 'success',
            size: 'medium',
          },
        };
        cellConfig.reject = {
          onClick: (item) => performAction(item, 'REJECT'),
          component: 'oxd-button',
          props: {
            label: $t('general.reject'),
            displayType: 'danger',
            size: 'medium',
          },
        };
      }
      return {
        props: {
          header: {
            cellConfig,
          },
        },
      };
    };

    const onSearch = () => execQuery();
    const onReset = () => {
      filters.value = {
        employee: null,
        status: {id: 'PENDING', label: 'PENDING'},
      };
      execQuery();
    };

    return {
      filters,
      statusOptions,
      headers,
      showPaginator,
      currentPage,
      total,
      pages,
      items: response,
      isLoading,
      onSearch,
      onReset,
      cellRenderer,
    };
  },
};
</script>
