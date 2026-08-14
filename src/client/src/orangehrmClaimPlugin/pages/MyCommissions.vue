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
    <oxd-table-filter :filter-title="$t('claim.my_commissions')">
      <oxd-form @submit-valid="loadItems">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
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
            type="submit"
            display-type="secondary"
            :label="$t('general.search')"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>
    <br />
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('claim.commissions') }}
        </oxd-text>
      </div>
      <oxd-divider />
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
      <div class="orangehrm-bottom-container">
        <oxd-text>
          {{ $t('claim.monthly_total') }}: {{ formattedTotal }}
        </oxd-text>
      </div>
    </div>
  </div>
</template>

<script>
import {ref, computed, onMounted} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import {formatDate, parseDate} from '@ohrm/core/util/helper/datefns';
import useDateFormat from '@/core/util/composable/useDateFormat';
import useLocale from '@/core/util/composable/useLocale';

const currentYearMonth = () => {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};

export default {
  name: 'MyCommissions',
  props: {
    empNumber: {
      type: Number,
      required: true,
    },
  },
  setup(props) {
    const isLoading = ref(false);
    const filters = ref({month: currentYearMonth()});
    const rules = {
      month: [
        required,
        (v) => /^\d{4}-\d{2}$/.test(String(v || '')) || 'YYYY-MM',
      ],
    };
    const items = ref([]);
    const totalAmount = ref(0);
    const {jsDateFormat} = useDateFormat();
    const {locale} = useLocale();

    const formattedTotal = computed(() =>
      Number(totalAmount.value || 0).toFixed(2),
    );

    const loadItems = async () => {
      if (!props.empNumber || !/^\d{4}-\d{2}$/.test(filters.value.month)) {
        return;
      }
      isLoading.value = true;
      try {
        const [year, month] = filters.value.month.split('-').map(Number);
        const http = new APIService(
          window.appGlobal.baseUrl,
          `/api/v2/claim/employees/${props.empNumber}/commissions`,
        );
        const {data} = await http.getAll({limit: 0, year, month});
        items.value = (data.data || []).map((item) => ({
          id: item.id,
          saleDateLabel: item.saleDate
            ? formatDate(parseDate(item.saleDate), jsDateFormat, {locale})
            : '',
          amountLabel:
            item.amount !== null && item.amount !== undefined
              ? Number(item.amount).toFixed(2)
              : '0.00',
          description: item.description || '',
        }));
        totalAmount.value = data.meta?.totalAmount ?? 0;
      } finally {
        isLoading.value = false;
      }
    };

    onMounted(loadItems);

    return {
      isLoading,
      filters,
      rules,
      items,
      formattedTotal,
      loadItems,
    };
  },
  data() {
    return {
      headers: [
        {
          name: 'saleDateLabel',
          slot: 'title',
          title: this.$t('claim.sale_date'),
          style: {flex: 2},
        },
        {
          name: 'description',
          title: this.$t('claim.product_service'),
          style: {flex: 3},
        },
        {
          name: 'amountLabel',
          title: this.$t('general.amount'),
          style: {flex: 1},
        },
      ],
    };
  },
};
</script>
