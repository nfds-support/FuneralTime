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
  <oxd-dialog @update:show="onCancel">
    <div class="orangehrm-modal-header">
      <oxd-text type="card-title">
        {{
          isEdit
            ? $t('claim.edit_commission')
            : $t('claim.add_commission')
        }}
      </oxd-text>
    </div>
    <oxd-divider />
    <oxd-form :loading="isLoading" @submit-valid="onSave">
      <oxd-form-row>
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <date-input
              v-model="form.saleDate"
              :label="$t('claim.sale_date')"
              :rules="rules.saleDate"
              :years="yearsArray"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="form.amount"
              :label="$t('general.amount')"
              :rules="rules.amount"
              required
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-form-row>
        <oxd-grid :cols="1" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="form.description"
              type="textarea"
              :label="$t('claim.product_service')"
              :rules="rules.description"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-divider />
      <oxd-form-actions>
        <required-text />
        <oxd-button
          type="button"
          display-type="ghost"
          :label="$t('general.cancel')"
          @click="onCancel"
        />
        <submit-button />
      </oxd-form-actions>
    </oxd-form>
  </oxd-dialog>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  validDateFormat,
  shouldNotExceedCharLength,
  maxCurrency,
  digitsOnlyWithTwoDecimalPoints,
} from '@/core/util/validation/rules';
import useDateFormat from '@/core/util/composable/useDateFormat';
import {yearRange} from '@ohrm/core/util/helper/year-range';
import {OxdDialog} from '@ohrm/oxd';

const emptyForm = {
  saleDate: null,
  amount: null,
  description: '',
};

export default {
  name: 'SaveCommissionModal',
  components: {
    'oxd-dialog': OxdDialog,
  },
  props: {
    empNumber: {
      type: Number,
      required: true,
    },
    data: {
      type: Object,
      default: null,
    },
  },
  emits: ['close'],
  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/claim/employees/${props.empNumber}/commissions`,
    );
    const {userDateFormat} = useDateFormat();
    return {http, userDateFormat};
  },
  data() {
    return {
      isLoading: false,
      yearsArray: [...yearRange()],
      form: {...emptyForm},
      rules: {
        saleDate: [required, validDateFormat(this.userDateFormat)],
        amount: [
          required,
          maxCurrency(10000000000),
          digitsOnlyWithTwoDecimalPoints,
        ],
        description: [shouldNotExceedCharLength(1000)],
      },
    };
  },
  computed: {
    isEdit() {
      return !!this.data?.id;
    },
  },
  beforeMount() {
    if (this.data) {
      this.form = {
        saleDate: this.data.saleDate,
        amount: this.data.amount,
        description: this.data.description || '',
      };
    }
  },
  methods: {
    onCancel() {
      this.$emit('close', true);
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        saleDate: this.form.saleDate,
        amount: Number(this.form.amount),
        description: this.form.description ? this.form.description.trim() : null,
      };
      const request = this.isEdit
        ? this.http.update(this.data.id, payload)
        : this.http.create(payload);
      request
        .then(() => {
          return this.isEdit
            ? this.$toast.updateSuccess()
            : this.$toast.saveSuccess();
        })
        .then(() => {
          this.$emit('close', true);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
