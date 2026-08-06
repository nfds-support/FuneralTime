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
          {{ $t('policy.acknowledgments') }}
        </oxd-text>
        <oxd-button
          :label="$t('general.back')"
          display-type="ghost"
          @click="onBack"
        />
      </div>
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:order="sortDefinition"
          :items="items.data"
          :headers="headers"
          :selectable="false"
          :loading="isLoading"
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
import {computed} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';

export default {
  props: {
    policyId: {type: Number, required: true},
  },
  setup(props) {
    const {$tEmpName} = useEmployeeNameTranslate();
    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: {'acknowledgment.acknowledgedAt': 'DESC'},
    });
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/policy/policies/${props.policyId}/acknowledgments`,
    );
    const {
      showPaginator,
      currentPage,
      total,
      pages,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      query: computed(() => ({
        sortField: sortField.value,
        sortOrder: sortOrder.value,
      })),
      normalizer: (data) =>
        data.map((item) => ({
          id: item.id,
          employee: $tEmpName(item.employee),
          acknowledgedAt: item.acknowledgedAt,
          ipAddress: item.ipAddress,
        })),
    });
    onSort(execQuery);
    const items = computed(() => response.value ?? {data: []});
    const headers = [
      {name: 'employee', title: 'Employee', style: {flex: 1}},
      {name: 'acknowledgedAt', title: 'Acknowledged At', sortField: 'acknowledgment.acknowledgedAt', style: {flex: 0.6}},
      {name: 'ipAddress', title: 'IP', style: {flex: 0.4}},
    ];
    return {
      headers,
      items,
      isLoading,
      showPaginator,
      currentPage,
      total,
      pages,
      sortDefinition,
      onBack: () => navigate('/policy/viewPolicies'),
    };
  },
};
</script>
