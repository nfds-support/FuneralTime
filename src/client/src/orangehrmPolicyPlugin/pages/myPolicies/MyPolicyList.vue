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
          {{ $t('policy.my_policies') }}
        </oxd-text>
        <oxd-button
          :label="$t('policy.open_learning')"
          display-type="secondary"
          @click="onLearning"
        />
      </div>
      <div class="orangehrm-container">
        <oxd-card-table
          :items="items"
          :headers="headers"
          :selectable="false"
          :clickable="false"
          :loading="isLoading"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
    </div>
    <oxd-dialog v-if="selected" :persistent="false" @update:show="onClose">
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">{{ selected.title }}</oxd-text>
      </div>
      <oxd-divider />
      <rich-text-display :html="selected.summary" />
      <rich-text-display
        v-if="selected.content"
        class="orangehrm-acknowledge-content"
        :html="selected.content"
      />
      <div v-if="selected.documentUrl" class="orangehrm-horizontal-padding">
        <a :href="selected.documentUrl" target="_blank" rel="noopener">
          {{ $t('policy.open_document') }}
        </a>
      </div>
      <div v-if="selected.moodleCourseUrl" class="orangehrm-horizontal-padding">
        <oxd-button
          :label="$t('policy.open_required_course')"
          display-type="ghost"
          @click="openCourse(selected.moodleCourseUrl)"
        />
      </div>
      <oxd-form-actions v-if="!selected.acknowledged">
        <oxd-button
          :label="$t('policy.acknowledge')"
          display-type="secondary"
          :disabled="acking"
          @click="onAcknowledge"
        />
      </oxd-form-actions>
      <oxd-text v-else tag="p">
        {{ $t('policy.acknowledged_on') }}: {{ selected.acknowledgedAt }}
      </oxd-text>
    </oxd-dialog>
  </div>
</template>

<script>
import {ref, onBeforeMount} from 'vue';
import {navigate} from '@/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';

export default {
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, '/api/v2/policy/my-policies');
    const items = ref([]);
    const isLoading = ref(false);
    const selected = ref(null);
    const acking = ref(false);

    const load = () => {
      isLoading.value = true;
      http
        .getAll({limit: 0})
        .then(({data}) => {
          items.value = data.data.map((item) => ({
            ...item,
            statusLabel: item.acknowledged ? 'Acknowledged' : 'Pending',
          }));
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    onBeforeMount(load);

    const headers = [
      {name: 'title', title: 'Policy', style: {flex: 1}},
      {name: 'version', title: 'Version', style: {flex: 0.4}},
      {name: 'dueDate', title: 'Due', style: {flex: 0.5}},
      {name: 'statusLabel', title: 'Status', style: {flex: 0.5}},
      {
        name: 'actions',
        slot: 'action',
        title: 'Actions',
        style: {flex: 0.5},
        cellType: 'oxd-table-cell-actions',
        cellConfig: {
          view: {
            onClick: (item) => {
              selected.value = item;
            },
            props: {name: 'eye-fill'},
          },
        },
      },
    ];

    const onAcknowledge = () => {
      acking.value = true;
      http
        .create({policyId: selected.value.id})
        .then(() => {
          selected.value = null;
          load();
        })
        .finally(() => {
          acking.value = false;
        });
    };

    const openCourse = (url) => window.open(url, '_blank', 'noopener');
    const onLearning = () => navigate('/policy/viewLearning');
    const onClose = (show) => {
      if (!show) selected.value = null;
    };

    return {
      items,
      headers,
      isLoading,
      selected,
      acking,
      onAcknowledge,
      openCourse,
      onLearning,
      onClose,
    };
  },
};
</script>
