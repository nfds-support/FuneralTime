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
    <oxd-table-filter :filter-title="$t('claim.assign_commissions')">
      <oxd-form @submit-valid="onSearch">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
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
          {{ $t('claim.commissions') }}
        </oxd-text>
        <oxd-button
          :label="$t('general.add')"
          icon-name="plus"
          display-type="secondary"
          @click="onClickAdd"
        />
      </div>
      <table-header
        :total="items.length"
        :loading="isLoading"
        :selected="checkedItems.length"
        @delete="onClickDeleteSelected"
      />
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          :items="items"
          :headers="headers"
          :selectable="true"
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
    <save-commission-modal
      v-if="showModal && empNumber"
      :emp-number="empNumber"
      :data="editData"
      @close="onCloseModal"
    />
    <delete-confirmation ref="deleteDialog"></delete-confirmation>
  </div>
</template>

<script>
import {ref, computed} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete.vue';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog.vue';
import SaveCommissionModal from '@/orangehrmClaimPlugin/components/SaveCommissionModal.vue';
import {formatDate, parseDate} from '@ohrm/core/util/helper/datefns';
import useDateFormat from '@/core/util/composable/useDateFormat';
import useLocale from '@/core/util/composable/useLocale';

export default {
  name: 'AssignCommissions',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
    'delete-confirmation': DeleteConfirmationDialog,
    'save-commission-modal': SaveCommissionModal,
  },
  setup() {
    const isLoading = ref(false);
    const filters = ref({employee: null, month: ''});
    const rules = {
      employee: [required],
      month: [
        (v) =>
          !v || /^\d{4}-\d{2}$/.test(String(v)) || 'YYYY-MM',
      ],
    };
    const items = ref([]);
    const checkedItems = ref([]);
    const totalAmount = ref(0);
    const showModal = ref(false);
    const editData = ref(null);
    const {jsDateFormat} = useDateFormat();
    const {locale} = useLocale();

    const empNumber = computed(() => filters.value.employee?.id ?? null);

    const monthQuery = computed(() => {
      const value = String(filters.value.month || '');
      if (!/^\d{4}-\d{2}$/.test(value)) {
        return {};
      }
      const [year, month] = value.split('-').map(Number);
      return {year, month};
    });

    const formattedTotal = computed(() =>
      Number(totalAmount.value || 0).toFixed(2),
    );

    const httpForEmployee = () => {
      return new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/claim/employees/${empNumber.value}/commissions`,
      );
    };

    const loadItems = async () => {
      if (!empNumber.value) return;
      isLoading.value = true;
      try {
        const {data} = await httpForEmployee().getAll({
          limit: 0,
          ...monthQuery.value,
        });
        items.value = (data.data || []).map((item) => ({
          id: item.id,
          saleDate: item.saleDate,
          saleDateLabel: item.saleDate
            ? formatDate(parseDate(item.saleDate), jsDateFormat, {locale})
            : '',
          amount: item.amount,
          amountLabel:
            item.amount !== null && item.amount !== undefined
              ? Number(item.amount).toFixed(2)
              : '0.00',
          description: item.description || '',
        }));
        totalAmount.value = data.meta?.totalAmount ?? 0;
        checkedItems.value = [];
      } finally {
        isLoading.value = false;
      }
    };

    return {
      isLoading,
      filters,
      rules,
      empNumber,
      items,
      checkedItems,
      formattedTotal,
      showModal,
      editData,
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
        {
          name: 'actions',
          title: this.$t('general.actions'),
          slot: 'action',
          style: {flex: 1},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            delete: {
              onClick: this.onClickDelete,
              component: 'oxd-icon-button',
              props: {name: 'trash'},
            },
            edit: {
              onClick: this.onClickEdit,
              props: {name: 'pencil-fill'},
            },
          },
        },
      ],
    };
  },
  methods: {
    onSearch() {
      this.loadItems();
    },
    onClickAdd() {
      this.editData = null;
      this.showModal = true;
    },
    onClickEdit(item) {
      this.editData = {
        id: item.id,
        saleDate: item.saleDate,
        amount: item.amount,
        description: item.description,
      };
      this.showModal = true;
    },
    onCloseModal() {
      this.showModal = false;
      this.editData = null;
      this.loadItems();
    },
    onClickDelete(item) {
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems([item.id]);
        }
      });
    },
    onClickDeleteSelected() {
      const ids = this.checkedItems.map((index) => this.items[index].id);
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems(ids);
        }
      });
    },
    async deleteItems(ids) {
      if (!this.empNumber || !ids.length) return;
      const http = new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/claim/employees/${this.empNumber}/commissions`,
      );
      await http.deleteAll({ids});
      this.$toast.deleteSuccess();
      await this.loadItems();
    },
  },
};
</script>
