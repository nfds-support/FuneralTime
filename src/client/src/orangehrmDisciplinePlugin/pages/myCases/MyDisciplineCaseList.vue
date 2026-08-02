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
          {{ $t('discipline.my_cases') }}
        </oxd-text>
        <oxd-button
          :label="$t('discipline.add_case')"
          icon-name="plus"
          display-type="secondary"
          @click="onClickAdd"
        />
      </div>
      <table-header :total="total" :loading="isLoading" />
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:order="sortDefinition"
          :items="items.data"
          :headers="headers"
          :selectable="false"
          :clickable="false"
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
import {ref} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';

const defaultSortOrder = {
  'disciplineCase.createdAt': 'DESC',
};

export default {
  setup() {
    const {sortDefinition, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/discipline/cases',
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
      normalizer: (data) =>
        data.map((item) => ({
          id: item.id,
          caseType: item.caseType,
          subject: item.subject,
          status: item.status,
          incidentDate: item.incidentDate,
        })),
      prefetch: true,
      toastNoRecords: false,
    });
    onSort(execQuery);
    return {
      items: response,
      sortDefinition,
      total,
      pages,
      showPaginator,
      currentPage,
      isLoading,
    };
  },
  data() {
    return {
      headers: [
        {
          name: 'caseType',
          title: this.$t('discipline.case_type'),
          sortField: 'disciplineCase.caseType',
          style: {flex: 1},
        },
        {
          name: 'subject',
          slot: 'title',
          title: this.$t('discipline.case_subject'),
          sortField: 'disciplineCase.subject',
          style: {flex: 1},
        },
        {
          name: 'status',
          title: this.$t('general.status'),
          sortField: 'disciplineCase.status',
          style: {flex: 1},
        },
        {
          name: 'incidentDate',
          title: this.$t('discipline.incident_date'),
          sortField: 'disciplineCase.incidentDate',
          style: {flex: 1},
        },
      ],
    };
  },
  methods: {
    onClickAdd() {
      navigate('/discipline/saveMyDisciplineCase');
    },
  },
};
</script>
