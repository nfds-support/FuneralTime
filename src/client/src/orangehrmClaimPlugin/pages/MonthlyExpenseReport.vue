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
    <oxd-table-filter :filter-title="$t('claim.monthly_expense_report')">
      <oxd-form @submit-valid="() => {}">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item v-if="!isEssOnly">
              <employee-autocomplete
                v-model="filters.employee"
                :rules="rules.employee"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.month"
                :label="$t('general.month')"
                placeholder="YYYY-MM"
                :rules="rules.month"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            display-type="secondary"
            :label="$t('claim.download_pdf')"
            :disabled="!canDownload"
            @click="download('pdf')"
          />
          <oxd-button
            display-type="ghost"
            class="orangehrm-left-space"
            :label="$t('claim.download_xlsx')"
            :disabled="!canDownload"
            @click="download('xlsx')"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
  </div>
</template>

<script>
import {computed, ref} from 'vue';
import {required} from '@/core/util/validation/rules';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete.vue';
import {navigate} from '@ohrm/core/util/helper/navigation';

export default {
  name: 'MonthlyExpenseReport',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  props: {
    empNumber: {
      type: Number,
      default: null,
    },
  },
  setup(props) {
    const isEssOnly = computed(() => {
      // When controller only passes self context, hide picker — Admin/Supervisor get picker.
      return false;
    });
    const filters = ref({
      employee: props.empNumber
        ? {id: props.empNumber, label: ''}
        : null,
      month: '',
    });
    const rules = {
      employee: [required],
      month: [
        required,
        (v) => /^\d{4}-\d{2}$/.test(String(v || '')) || 'YYYY-MM',
      ],
    };

    const canDownload = computed(() => {
      const empId = filters.value.employee?.id || props.empNumber;
      return !!empId && /^\d{4}-\d{2}$/.test(String(filters.value.month || ''));
    });

    const download = (format) => {
      const empId = filters.value.employee?.id || props.empNumber;
      if (!empId || !filters.value.month) return;
      const url =
        `${window.appGlobal.baseUrl}/claim/downloadMonthlyExpenseReport` +
        `?empNumber=${encodeURIComponent(empId)}` +
        `&month=${encodeURIComponent(filters.value.month)}` +
        `&format=${encodeURIComponent(format)}`;
      window.location.href = url;
    };

    return {
      isEssOnly,
      filters,
      rules,
      canDownload,
      download,
      navigate,
    };
  },
};
</script>
