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
          {{ $t('policy.policies') }}
        </oxd-text>
        <div>
          <oxd-button
            :label="$t('policy.moodle_settings')"
            display-type="ghost"
            class="orangehrm-horizontal-margin"
            @click="onMoodleConfig"
          />
          <oxd-button
            :label="$t('policy.add_policy')"
            icon-name="plus"
            display-type="secondary"
            @click="onClickAdd"
          />
        </div>
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
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

const defaultSortOrder = {
  'policy.createdAt': 'DESC',
};

export default {
  components: {
    'delete-confirmation': DeleteConfirmationDialog,
  },
  setup() {
    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });
    const http = new APIService(window.appGlobal.baseUrl, '/api/v2/policy/policies');
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
      query: computed(() => ({
        sortField: sortField.value,
        sortOrder: sortOrder.value,
      })),
    });
    onSort(execQuery);

    const checkedItems = ref([]);
    const items = computed(() => response.value ?? {data: []});

    const headers = [
      {name: 'title', title: 'Title', sortField: 'policy.title', style: {flex: 1}},
      {name: 'version', title: 'Version', style: {flex: 0.4}},
      {name: 'status', title: 'Status', sortField: 'policy.status', style: {flex: 0.5}},
      {name: 'effectiveDate', title: 'Effective', sortField: 'policy.effectiveDate', style: {flex: 0.5}},
      {
        name: 'actions',
        slot: 'action',
        title: 'Actions',
        style: {flex: 0.7},
        cellType: 'oxd-table-cell-actions',
        cellConfig: {
          edit: {
            onClick: (item) => navigate(`/policy/editPolicy/${item.id}`),
            props: {name: 'pencil-fill'},
          },
          acknowledgments: {
            onClick: (item) => navigate(`/policy/viewPolicyAcknowledgments/${item.id}`),
            props: {name: 'list-ul'},
          },
          delete: {
            onClick: (item) => onClickDelete([item.id]),
            props: {name: 'trash'},
          },
        },
      },
    ];

    const deleteConfirmation = ref(null);
    const onClickDelete = (ids) => {
      deleteConfirmation.value.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          http.deleteAll({ids}).then(() => execQuery());
        }
      });
    };
    const onClickDeleteSelected = () => onClickDelete(checkedItems.value);
    const onClickAdd = () => navigate('/policy/savePolicy');
    const onMoodleConfig = () => navigate('/policy/moodleConfig');

    return {
      headers,
      items,
      checkedItems,
      isLoading,
      showPaginator,
      currentPage,
      total,
      pages,
      pageSize,
      sortDefinition,
      deleteConfirmation,
      onClickDeleteSelected,
      onClickAdd,
      onMoodleConfig,
    };
  },
};
</script>
