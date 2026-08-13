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
          {{ $t('discipline.cases') }}
        </oxd-text>
        <oxd-button
          :label="$t('discipline.add_case')"
          icon-name="plus"
          display-type="secondary"
          @click="onClickAdd"
        />
      </div>
      <table-header
        :selected="checkedItems"
        :total="total"
        :loading="isLoading"
        @delete="onClickDeleteSelected"
      />
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          v-model:order="sortDefinition"
          :items="items.data"
          :headers="headers"
          :selectable="true"
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
    <delete-confirmation ref="deleteConfirmation" />
  </div>
</template>

<script>
import {computed, ref} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

const defaultFilters = {
  caseType: null,
  status: null,
};

const defaultSortOrder = {
  'disciplineCase.createdAt': 'DESC',
};

export default {
  components: {
    'delete-confirmation': DeleteConfirmationDialog,
  },
  setup() {
    const {$tEmpName} = useEmployeeNameTranslate();
    const filters = ref({...defaultFilters});
    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/discipline/cases',
    );

    const getNormalizer = (data) => {
      return data.map((item) => {
        return {
          id: item.id,
          caseType: item.caseType,
          subject: item.subject,
          complaintSource: item.complaintSource,
          status: item.status,
          severity: item.severity,
          incidentDate: item.incidentDate,
          employee: $tEmpName(item.employee),
        };
      });
    };

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      pageSize,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      query: filters,
      normalizer: getNormalizer,
      prefetch: true,
      toastNoRecords: false,
    });

    onSort(execQuery);

    return {
      http,
      items: response,
      sortDefinition,
      filters,
      total,
      pages,
      pageSize,
      showPaginator,
      currentPage,
      isLoading,
      execQuery,
      checkedItems: ref([]),
    };
  },
  data() {
    return {
      headers: [
        {
          name: 'employee',
          slot: 'title',
          title: this.$t('general.employee_name'),
          sortField: 'employee.lastName',
          style: {flex: 1},
        },
        {
          name: 'caseType',
          title: this.$t('discipline.case_type'),
          sortField: 'disciplineCase.caseType',
          style: {flex: 1},
        },
        {
          name: 'subject',
          title: this.$t('discipline.case_subject'),
          sortField: 'disciplineCase.subject',
          style: {flex: 1},
        },
        {
          name: 'complaintSource',
          title: this.$t('discipline.complaint_source'),
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
        {
          name: 'actions',
          title: this.$t('general.actions'),
          slot: 'action',
          style: {flex: 1},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            edit: {
              onClick: this.onClickEdit,
              props: {
                name: 'pencil-fill',
              },
            },
            delete: {
              onClick: this.onClickDelete,
              component: 'oxd-icon-button',
              props: {
                name: 'trash',
              },
            },
          },
        },
      ],
    };
  },
  methods: {
    onClickAdd() {
      navigate('/discipline/saveDisciplineCase');
    },
    onClickEdit(item) {
      navigate('/discipline/editDisciplineCase/{id}', {id: item.id});
    },
    onClickDelete(item) {
      this.$refs.deleteConfirmation.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems([item.id]);
        }
      });
    },
    onClickDeleteSelected() {
      const ids = this.checkedItems.map((item) =>
        typeof item === 'object' ? item.id : item,
      );
      this.$refs.deleteConfirmation.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems(ids);
        }
      });
    },
    deleteItems(items) {
      this.isLoading = true;
      this.http
        .deleteAll({ids: items})
        .then(() => this.$toast.deleteSuccess())
        .then(() => {
          this.checkedItems = [];
          return this.execQuery();
        });
    },
  },
};
</script>
