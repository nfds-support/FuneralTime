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
  <oxd-time-input
    class="timesheet-time-input"
    :model-value="normalizedValue"
    :step="15"
    :placeholder="placeholder"
    @update:model-value="onUpdate"
  />
</template>

<script>
import {OxdTimeInput} from '@ohrm/oxd';

/**
 * Same AM/PM clock picker as Assign Leave, but:
 * - uses OxdTimeInput directly (no oxd-input-field / form registration)
 * - ignores no-op emits so the picker's watchEffect cannot lock the page
 */
export default {
  name: 'TimesheetTimeInput',
  components: {
    'oxd-time-input': OxdTimeInput,
  },
  props: {
    modelValue: {
      type: String,
      default: null,
    },
    placeholder: {
      type: String,
      default: 'hh:mm a',
    },
  },
  emits: ['update:modelValue'],
  computed: {
    normalizedValue() {
      return this.modelValue ? this.modelValue : null;
    },
  },
  methods: {
    normalizeTime(value) {
      if (!value || typeof value !== 'string') {
        return null;
      }
      const match = value.trim().match(/^(\d{1,2}):(\d{2})$/);
      if (!match) {
        return value;
      }
      const hour = String(Number(match[1])).padStart(2, '0');
      const minute = match[2];
      return `${hour}:${minute}`;
    },
    onUpdate(value) {
      const next = this.normalizeTime(value);
      const current = this.normalizeTime(this.modelValue);
      if (next === current) {
        return;
      }
      this.$emit('update:modelValue', next);
    },
  },
};
</script>

<style lang="scss" scoped>
.timesheet-time-input {
  width: 100%;
  min-width: 6rem;
}
::v-deep(.oxd-time-input .oxd-input) {
  min-width: 5.5rem;
  font-size: 0.75rem;
  padding-left: 0.4rem;
  padding-right: 2rem;
}
::v-deep(.oxd-time-input--clock) {
  width: 1.75rem;
  height: 1.75rem;
}
</style>
