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
          {{ $t('union.unions') }}
        </oxd-text>
        <oxd-button
          :label="$t('union.add_union')"
          icon-name="plus"
          display-type="secondary"
          @click="navigate('/union/saveUnion')"
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
import {ref} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

export default {
  components: {'delete-confirmation': DeleteConfirmationDialog},
  setup() {
    const {sortDefinition, onSort} = useSort({
      sortDefinition: {'laborUnion.name': 'ASC'},
    });
    const http = new APIService(window.appGlobal.baseUrl, '/api/v2/union/unions');
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
          name: item.name,
          description: item.description,
          active: item.active ? 'Active' : 'Inactive',
        })),
      prefetch: true,
      toastNoRecords: false,
    });
    onSort(execQuery);
    return {
      http,
      navigate,
      items: response,
      sortDefinition,
      total,
      pages,
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
          name: 'name',
          slot: 'title',
          title: this.$t('general.name'),
          sortField: 'laborUnion.name',
          style: {flex: 1},
        },
        {
          name: 'description',
          title: this.$t('general.description'),
          style: {flex: 1},
        },
        {
          name: 'active',
          title: this.$t('general.status'),
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
              onClick: (item) =>
                navigate('/union/editUnion/{id}', {id: item.id}),
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
    onClickDelete(item) {
      this.$refs.deleteConfirmation.showDialog().then((confirmation) => {
        if (confirmation === 'ok') this.deleteItems([item.id]);
      });
    },
    onClickDeleteSelected() {
      const ids = this.checkedItems.map((item) =>
        typeof item === 'object' ? item.id : item,
      );
      this.$refs.deleteConfirmation.showDialog().then((confirmation) => {
        if (confirmation === 'ok') this.deleteItems(ids);
      });
    },
    deleteItems(ids) {
      this.http
        .deleteAll({ids})
        .then(() => this.$toast.deleteSuccess())
        .then(() => {
          this.checkedItems = [];
          return this.execQuery();
        });
    },
  },
};
</script>
