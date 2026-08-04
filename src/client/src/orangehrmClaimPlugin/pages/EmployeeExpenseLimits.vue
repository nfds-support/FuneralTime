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
    <oxd-table-filter :filter-title="$t('claim.expense_limits')">
      <oxd-form @submit-valid="onLoad">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <employee-autocomplete
                v-model="filters.employee"
                :rules="rules.employee"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('general.search')"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div v-if="empNumber" class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('claim.expense_limits') }}
        </oxd-text>
        <oxd-text tag="p">
          {{ $t('claim.mileage_rate') }}:
          {{ mileageRate ?? '0.55' }}
        </oxd-text>
      </div>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row v-for="row in limitRows" :key="row.expenseTypeId">
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-text tag="p">{{ row.name }}</oxd-text>
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="row.monthlyLimit"
                :label="$t('claim.monthly_limit')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('general.save')"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {ref, computed} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete.vue';

export default {
  name: 'EmployeeExpenseLimits',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  setup() {
    const isLoading = ref(false);
    const filters = ref({employee: null});
    const rules = {employee: [required]};
    const expenseTypes = ref([]);
    const existingLimits = ref([]);
    const mileageRate = ref(null);
    const limitRows = ref([]);

    const typesHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/claim/expenses/types',
    );
    const jobHttpBase = window.appGlobal.baseUrl;

    const empNumber = computed(() => filters.value.employee?.id ?? null);

    const buildRows = () => {
      const byType = {};
      existingLimits.value.forEach((limit) => {
        byType[limit.expenseType.id] = limit;
      });
      limitRows.value = expenseTypes.value
        .filter((t) => t.reportColumn && t.reportColumn !== 'mileage')
        .map((t) => ({
          expenseTypeId: t.id,
          name: t.name,
          id: byType[t.id]?.id ?? null,
          monthlyLimit: byType[t.id]?.monthlyLimit ?? '',
        }));
    };

    const onLoad = async () => {
      if (!empNumber.value) return;
      isLoading.value = true;
      try {
        if (!expenseTypes.value.length) {
          const {data} = await typesHttp.getAll({limit: 0, status: true});
          expenseTypes.value = data.data || [];
        }
        const limitsHttp = new APIService(
          jobHttpBase,
          `/api/v2/claim/employees/${empNumber.value}/expense-limits`,
        );
        const limitsRes = await limitsHttp.getAll({limit: 0});
        existingLimits.value = limitsRes.data.data || [];

        const jobHttp = new APIService(
          jobHttpBase,
          `/api/v2/pim/employees/${empNumber.value}/job-details`,
        );
        const jobRes = await jobHttp.getAll();
        mileageRate.value = jobRes.data.data?.mileageReimbursementRate ?? '0.55';
        buildRows();
      } finally {
        isLoading.value = false;
      }
    };

    const onSave = async () => {
      if (!empNumber.value) return;
      isLoading.value = true;
      const limitsHttp = new APIService(
        jobHttpBase,
        `/api/v2/claim/employees/${empNumber.value}/expense-limits`,
      );
      try {
        for (const row of limitRows.value) {
          const value = String(row.monthlyLimit ?? '').trim();
          if (value === '') {
            if (row.id) {
              await limitsHttp.deleteAll({ids: [row.id]});
            }
            continue;
          }
          if (row.id) {
            await limitsHttp.update(row.id, {
              expenseTypeId: row.expenseTypeId,
              monthlyLimit: Number(value),
            });
          } else {
            await limitsHttp.create({
              expenseTypeId: row.expenseTypeId,
              monthlyLimit: Number(value),
            });
          }
        }
        await onLoad();
      } finally {
        isLoading.value = false;
      }
    };

    return {
      isLoading,
      filters,
      rules,
      empNumber,
      limitRows,
      mileageRate,
      onLoad,
      onSave,
    };
  },
};
</script>
