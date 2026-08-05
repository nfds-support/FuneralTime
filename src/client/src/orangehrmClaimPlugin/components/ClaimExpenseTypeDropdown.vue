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
  <oxd-input-field
    v-model="selected"
    type="select"
    :label="label"
    :options="options"
    :rules="rules"
    required
  />
</template>

<script>
import {ref, onBeforeMount, watch, computed} from 'vue';
import {APIService} from '@ohrm/core/util/services/api.service';

export default {
  name: 'ClaimExpenseTypeDropdown',
  props: {
    modelValue: {
      type: [Object, null],
      default: null,
    },
    label: {
      type: String,
      default: '',
    },
    rules: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
  setup(props, {emit}) {
    const options = ref([]);
    const selected = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value),
    });
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/claim/expenses/types',
    );
    onBeforeMount(() => {
      http.getAll({limit: 0, status: true}).then(({data}) => {
        options.value = data.data.map((item) => {
          return {
            id: item.id,
            label: item.name,
            reportColumn: item.reportColumn,
          };
        });
      });
    });
    return {
      options,
      selected,
    };
  },
};
</script>
