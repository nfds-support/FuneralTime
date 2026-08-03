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
    type="select"
    :label="label || $t('union.union')"
    :options="options"
    :show-empty-selector="showEmptySelector"
  />
</template>

<script>
import {ref, onBeforeMount} from 'vue';
import {APIService} from '@/core/util/services/api.service';

export default {
  name: 'UnionDropdown',
  props: {
    label: {
      type: String,
      default: null,
    },
    showEmptySelector: {
      type: Boolean,
      default: true,
    },
    activeOnly: {
      type: Boolean,
      default: true,
    },
  },
  setup(props) {
    const options = ref([]);
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/union/unions',
    );

    onBeforeMount(() => {
      http
        .getAll({
          limit: 0,
          ...(props.activeOnly ? {active: true} : {}),
        })
        .then(({data}) => {
          options.value = (data.data || []).map((item) => ({
            id: item.id,
            label: item.name,
          }));
        });
    });

    return {options};
  },
};
</script>
