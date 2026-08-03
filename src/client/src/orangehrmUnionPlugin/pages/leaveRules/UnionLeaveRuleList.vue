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
        <div>
          <oxd-text tag="h6" class="orangehrm-main-title">
            {{ $t('union.leave_rules') }}
          </oxd-text>
          <oxd-text tag="p" class="orangehrm-input-hint">
            {{ $t('union.leave_rule_hint') }}
          </oxd-text>
        </div>
        <div class="orangehrm-form-action">
          <oxd-button
            display-type="ghost"
            :label="$t('union.generate_entitlements')"
            @click="showGenerate = true"
          />
          <oxd-button
            :label="$t('union.add_leave_rule')"
            icon-name="plus"
            display-type="secondary"
            @click="navigate('/union/saveUnionLeaveRule')"
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

    <oxd-dialog v-if="showGenerate" @update:show="showGenerate = $event">
      <div class="orangehrm-dialog-modal">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('union.generate_entitlements') }}
        </oxd-text>
        <oxd-divider />
        <oxd-form :loading="generating" @submit-valid="onGenerate">
          <oxd-input-field
            v-model="generate.leaveType"
            type="select"
            required
            :label="$t('leave.leave_type')"
            :options="leaveTypes"
            :rules="[required]"
          />
          <date-input
            v-model="generate.fromDate"
            required
            :label="$t('general.from_date')"
            :rules="[required]"
          />
          <date-input
            v-model="generate.toDate"
            required
            :label="$t('general.to_date')"
            :rules="[required]"
          />
          <oxd-form-actions>
            <oxd-button
              display-type="ghost"
              :label="$t('general.cancel')"
              @click="showGenerate = false"
            />
            <submit-button :label="$t('union.generate_entitlements')" />
          </oxd-form-actions>
        </oxd-form>
      </div>
    </oxd-dialog>
    <delete-confirmation ref="deleteConfirmation" />
  </div>
</template>

<script>
import {ref} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import {required} from '@/core/util/validation/rules';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';
import {OxdDialog} from '@ohrm/oxd';

export default {
  components: {
    'delete-confirmation': DeleteConfirmationDialog,
    'oxd-dialog': OxdDialog,
  },
  setup() {
    const {sortDefinition, onSort} = useSort({
      sortDefinition: {'rule.minYears': 'ASC'},
    });
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/union/leave-rules',
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
          union: item.union?.name || 'Company Default',
          leaveType: item.leaveType?.name,
          minYears: item.minYears,
          maxYears: item.maxYears ?? '∞',
          entitlementDays: item.entitlementDays,
        })),
      prefetch: true,
      toastNoRecords: false,
    });
    onSort(execQuery);
    return {
      http,
      navigate,
      required,
      items: response,
      sortDefinition,
      total,
      pages,
      showPaginator,
      currentPage,
      isLoading,
      execQuery,
      checkedItems: ref([]),
      showGenerate: ref(false),
      generating: ref(false),
      leaveTypes: ref([]),
      generate: ref({leaveType: null, fromDate: '', toDate: ''}),
    };
  },
  data() {
    return {
      headers: [
        {name: 'union', title: this.$t('union.union'), style: {flex: 1}},
        {
          name: 'leaveType',
          slot: 'title',
          title: this.$t('leave.leave_type'),
          style: {flex: 1},
        },
        {
          name: 'minYears',
          title: this.$t('union.min_years'),
          sortField: 'rule.minYears',
          style: {flex: 1},
        },
        {name: 'maxYears', title: this.$t('union.max_years'), style: {flex: 1}},
        {
          name: 'entitlementDays',
          title: this.$t('union.entitlement_days'),
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
                navigate('/union/editUnionLeaveRule/{id}', {id: item.id}),
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
  beforeMount() {
    new APIService(window.appGlobal.baseUrl, '/api/v2/leave/leave-types')
      .getAll({limit: 0})
      .then(({data}) => {
        this.leaveTypes = (data.data || []).map((item) => ({
          id: item.id,
          label: item.name,
        }));
      });
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
    onGenerate() {
      this.generating = true;
      new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/union/leave-entitlement-generation',
      )
        .create({
          leaveTypeId: this.generate.leaveType.id,
          fromDate: this.generate.fromDate,
          toDate: this.generate.toDate,
        })
        .then(({data}) => {
          const result = data.data;
          this.$toast.success({
            title: this.$t('general.success'),
            message: `Generated ${result.generated}, skipped ${result.skipped}`,
          });
          this.showGenerate = false;
        })
        .finally(() => {
          this.generating = false;
        });
    },
  },
};
</script>
