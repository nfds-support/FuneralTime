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

    <oxd-dialog v-if="selected" :persistent="false" @update:show="onClose">
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">{{ selected.subject }}</oxd-text>
      </div>
      <oxd-divider />
      <oxd-form-row>
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-text tag="p" class="orangehrm-form-hint">
              {{ $t('discipline.case_type') }}: {{ selected.caseType }}
            </oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text tag="p" class="orangehrm-form-hint">
              {{ $t('general.status') }}: {{ selected.status }}
            </oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text tag="p" class="orangehrm-form-hint">
              {{ $t('discipline.severity') }}: {{ selected.severity || '—' }}
            </oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text tag="p" class="orangehrm-form-hint">
              {{ $t('discipline.incident_date') }}:
              {{ selected.incidentDate || '—' }}
            </oxd-text>
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-divider />
      <oxd-text tag="p" class="orangehrm-form-hint">
        {{ $t('general.description') }}
      </oxd-text>
      <rich-text-display :html="selected.description" />
      <oxd-text tag="p" class="orangehrm-form-hint">
        {{ $t('discipline.more_details') }}
      </oxd-text>
      <rich-text-display :html="selected.details" />
      <oxd-text tag="p" class="orangehrm-form-hint">
        {{ $t('discipline.action_plan') }}
      </oxd-text>
      <rich-text-display :html="selected.actionPlan" />
      <oxd-text tag="p" class="orangehrm-form-hint">
        {{ $t('discipline.action_taken') }}
      </oxd-text>
      <rich-text-display :html="selected.actionTaken" />
    </oxd-dialog>
  </div>
</template>

<script>
import {computed, ref} from 'vue';
import usei18n from '@/core/util/composable/usei18n';
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';

const defaultSortOrder = {
  'disciplineCase.createdAt': 'DESC',
};

export default {
  setup() {
    const {$t} = usei18n();
    const {sortDefinition, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/discipline/cases',
    );
    const selected = ref(null);
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
          _raw: item,
        })),
      prefetch: true,
      toastNoRecords: false,
    });
    onSort(execQuery);

    const onClickView = (item) => {
      selected.value = item._raw || item;
    };
    const onClose = () => {
      selected.value = null;
    };

    const headers = computed(() => [
      {
        name: 'caseType',
        title: $t('discipline.case_type'),
        sortField: 'disciplineCase.caseType',
        style: {flex: 1},
      },
      {
        name: 'subject',
        slot: 'title',
        title: $t('discipline.case_subject'),
        sortField: 'disciplineCase.subject',
        style: {flex: 1},
      },
      {
        name: 'status',
        title: $t('general.status'),
        sortField: 'disciplineCase.status',
        style: {flex: 1},
      },
      {
        name: 'incidentDate',
        title: $t('discipline.incident_date'),
        sortField: 'disciplineCase.incidentDate',
        style: {flex: 1},
      },
      {
        name: 'actions',
        slot: 'action',
        title: $t('general.actions'),
        style: {flex: 0.5},
        cellType: 'oxd-table-cell-actions',
        cellConfig: {
          view: {
            onClick: onClickView,
            props: {name: 'eye-fill'},
          },
        },
      },
    ]);

    return {
      items: response,
      sortDefinition,
      total,
      pages,
      showPaginator,
      currentPage,
      isLoading,
      selected,
      headers,
      onClose,
    };
  },
};
</script>
