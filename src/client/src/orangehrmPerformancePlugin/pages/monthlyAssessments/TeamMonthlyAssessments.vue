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
          {{ $t('performance.team_monthly_assessments') }}
        </oxd-text>
        <oxd-button
          :label="$t('performance.start_assessment')"
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
          :headers="headers"
          :items="items?.data"
          :selectable="true"
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
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import useSort from '@ohrm/core/util/composable/useSort';
import {navigate} from '@/core/util/helper/navigation';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

const defaultSortOrder = {
  'monthlyAssessment.periodYear': 'DESC',
  'monthlyAssessment.periodMonth': 'DESC',
};

const monthNames = [
  '',
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

export default {
  components: {
    'delete-confirmation': DeleteConfirmationDialog,
  },
  setup() {
    const {$tEmpName} = useEmployeeNameTranslate();
    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });
    const checkedItems = ref([]);

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/performance/monthly-assessments',
    );

    const query = computed(() => ({
      sortField: sortField.value,
      sortOrder: sortOrder.value,
    }));

    const normalizer = (data) =>
      data.map((item) => ({
        id: item.id,
        employee: $tEmpName(item.employee),
        period: `${monthNames[item.periodMonth] || item.periodMonth} ${
          item.periodYear
        }`,
        status: item.status,
        employeeRating: item.employeeOverallRating,
        managerRating: item.managerOverallRating,
      }));

    const {
      currentPage,
      total,
      showPaginator,
      pages,
      response,
      execQuery,
      isLoading,
    } = usePaginate(http, {
      query,
      normalizer,
      toastNoRecords: false,
    });

    onSort(execQuery);

    return {
      http,
      total,
      isLoading,
      items: response,
      sortDefinition,
      showPaginator,
      pages,
      currentPage,
      checkedItems,
      execQuery,
    };
  },
  data() {
    return {
      headers: [
        {
          name: 'employee',
          slot: 'title',
          title: this.$t('general.employee_name'),
          style: {flex: 1},
        },
        {
          name: 'period',
          title: this.$t('performance.assessment_period'),
          style: {flex: 1},
        },
        {
          name: 'status',
          title: this.$t('general.status'),
          style: {flex: 1},
        },
        {
          name: 'employeeRating',
          title: this.$t('performance.self_rating'),
          style: {flex: 1},
        },
        {
          name: 'managerRating',
          title: this.$t('performance.manager_rating'),
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
              props: {name: 'pencil-fill'},
            },
            delete: {
              onClick: this.onClickDelete,
              component: 'oxd-icon-button',
              props: {name: 'trash'},
            },
          },
        },
      ],
    };
  },
  methods: {
    onClickAdd() {
      navigate('/performance/saveMonthlyAssessment', undefined, {
        mode: 'manager',
      });
    },
    onClickEdit(item) {
      navigate('/performance/editMonthlyAssessment/{id}', {id: item.id}, {
        mode: 'manager',
      });
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
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
