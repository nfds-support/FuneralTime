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
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{
          editingId
            ? $t('time.edit_payroll_period')
            : $t('time.add_payroll_period')
        }}
      </oxd-text>
      <oxd-divider />
      <oxd-text tag="p" class="orangehrm-form-hint">
        {{ $t('time.payroll_period_fiscal_hint') }}
      </oxd-text>
      <oxd-form :loading="isSaving" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.title"
                :label="$t('time.period_title')"
                :rules="rules.title"
                placeholder="P20"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.startDate"
                :label="$t('general.from')"
                :rules="rules.startDate"
                placeholder="YYYY-MM-DD"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.endDate"
                :label="$t('general.to')"
                :rules="rules.endDate"
                placeholder="YYYY-MM-DD"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            v-if="editingId"
            type="button"
            display-type="ghost"
            :label="$t('general.cancel')"
            @click="resetForm"
          />
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('general.save')"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>

    <br />

    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('time.payroll_periods') }}
        </oxd-text>
      </div>
      <table-header
        :selected="checkedItems.length"
        :total="total"
        :loading="isLoading"
        @delete="onClickDeleteSelected"
      ></table-header>
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          v-model:order="sortDefinition"
          :headers="headers"
          :items="items?.data"
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

    <delete-confirmation ref="deleteDialog"></delete-confirmation>
  </div>
</template>

<script>
import {computed, ref, watch} from 'vue';
import {
  required,
  validDateFormat,
  endDateShouldBeAfterStartDate,
} from '@/core/util/validation/rules';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import useSort from '@ohrm/core/util/composable/useSort';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog.vue';
import usei18n from '@/core/util/composable/usei18n';

const defaultSortOrder = {
  'payrollPeriod.startDate': 'ASC',
};

const emptyForm = () => ({
  title: '',
  startDate: '',
  endDate: '',
});

const parsePeriodNumber = (value) => {
  const match = String(value ?? '')
    .trim()
    .match(/(\d+)/);
  return match ? Number(match[1]) : NaN;
};

const addDays = (ymd, days) => {
  const match = String(ymd).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return '';
  }
  const date = new Date(
    Number(match[1]),
    Number(match[2]) - 1,
    Number(match[3]),
  );
  date.setDate(date.getDate() + days);
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
};

export default {
  components: {
    'delete-confirmation': DeleteConfirmationDialog,
  },
  setup() {
    const {$t} = usei18n();
    const form = ref(emptyForm());
    const editingId = ref(null);
    const isSaving = ref(false);

    const rules = {
      title: [
        required,
        (v) =>
          Number.isInteger(parsePeriodNumber(v)) && parsePeriodNumber(v) > 0
            ? true
            : $t('time.period_title_number_required'),
      ],
      startDate: [required, validDateFormat()],
      endDate: [
        required,
        validDateFormat(),
        endDateShouldBeAfterStartDate(
          () => form.value.startDate,
          undefined,
          {allowSameDate: true},
        ),
      ],
    };

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/payroll-periods',
    );

    const periodNormalizer = (data) =>
      data.map((item) => ({
        id: item.id,
        title: item.title || item.label || `P${item.periodNumber}`,
        startDate: item.startDate,
        endDate: item.endDate,
        periodNumber: item.periodNumber,
      }));

    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });
    const serializedFilters = computed(() => ({
      sortField: sortField.value,
      sortOrder: sortOrder.value,
    }));
    const {
      showPaginator,
      currentPage,
      total,
      pages,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      query: serializedFilters,
      normalizer: periodNormalizer,
    });
    onSort(execQuery);

    watch(
      () => form.value.startDate,
      (value, previous) => {
        if (!value || editingId.value) {
          return;
        }
        if (!form.value.endDate || form.value.endDate === addDays(previous, 13)) {
          form.value.endDate = addDays(value, 13);
        }
      },
    );

    return {
      form,
      editingId,
      isSaving,
      rules,
      http,
      showPaginator,
      currentPage,
      total,
      pages,
      items: response,
      isLoading,
      execQuery,
      sortDefinition,
    };
  },
  data() {
    return {
      checkedItems: [],
      headers: [
        {
          name: 'title',
          slot: 'title',
          title: this.$t('time.period_title'),
          style: {flex: 1},
        },
        {
          name: 'startDate',
          title: this.$t('general.from'),
          sortField: 'payrollPeriod.startDate',
          style: {flex: 1},
        },
        {
          name: 'endDate',
          title: this.$t('general.to'),
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
              onClick: this.onClickEdit,
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
  methods: {
    resetForm() {
      this.editingId = null;
      this.form = emptyForm();
    },
    onClickEdit(item) {
      this.editingId = item.id;
      this.form = {
        title: item.title,
        startDate: item.startDate,
        endDate: item.endDate,
      };
    },
    onSave() {
      const periodNumber = parsePeriodNumber(this.form.title);
      if (!Number.isInteger(periodNumber) || periodNumber < 1) {
        return;
      }
      const payload = {
        title: String(this.form.title).trim(),
        periodNumber,
        startDate: this.form.startDate,
        endDate: this.form.endDate,
      };
      this.isSaving = true;
      const request = this.editingId
        ? this.http.update(this.editingId, payload)
        : this.http.create(payload);
      request
        .then(() => {
          return this.editingId
            ? this.$toast.updateSuccess()
            : this.$toast.saveSuccess();
        })
        .then(() => {
          this.resetForm();
          return this.resetDataTable();
        })
        .finally(() => {
          this.isSaving = false;
        });
    },
    onClickDeleteSelected() {
      const ids = this.checkedItems.map((index) => this.items?.data[index].id);
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems(ids);
        }
      });
    },
    onClickDelete(item) {
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems([item.id]);
        }
      });
    },
    deleteItems(ids) {
      if (!(ids instanceof Array) || !ids.length) {
        return;
      }
      this.isLoading = true;
      this.http
        .deleteAll({ids})
        .then(() => this.$toast.deleteSuccess())
        .then(() => {
          if (this.editingId && ids.includes(this.editingId)) {
            this.resetForm();
          }
          return this.resetDataTable();
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    async resetDataTable() {
      this.checkedItems = [];
      await this.execQuery();
    },
  },
};
</script>
