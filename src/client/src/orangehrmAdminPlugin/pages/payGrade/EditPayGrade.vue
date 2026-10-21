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
        {{ $t('admin.edit_pay_grade') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="grade.name"
                :label="$t('general.name')"
                :rules="rules.name"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-if="existingCurrencyId"
                v-model="currencyLabel"
                :label="$t('general.currency')"
                readonly
                disabled
              />
              <oxd-input-field
                v-else
                v-model="grade.currencyId"
                type="select"
                :label="$t('general.currency')"
                :options="allowedCurrencies"
                :rules="rules.currencyId"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="grade.minSalary"
                :label="$t('admin.minimum_salary')"
                :rules="rules.minSalary"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="grade.maxSalary"
                :label="$t('admin.maximum_salary')"
                :rules="rules.maxSalary"
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
    </div>

    <pay-grade-currency
      v-if="payGradeId"
      :pay-grade-id="payGradeId"
    ></pay-grade-currency>
  </div>
</template>

<script>
import {navigate, reloadPage} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
  maxCurrency,
  digitsOnlyWithTwoDecimalPoints,
} from '@ohrm/core/util/validation/rules';
import {
  maxValueShouldBeGreaterThanMinValue,
  minValueShouldBeLowerThanMaxValue,
} from '@/core/util/validation/rules';
import useServerValidation from '@/core/util/composable/useServerValidation';
import PayGradeCurrency from '@/orangehrmAdminPlugin/pages/payGrade/PayGradeCurrency.vue';

export default {
  components: {
    'pay-grade-currency': PayGradeCurrency,
  },

  props: {
    payGradeId: {
      type: Number,
      required: true,
    },
  },

  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/pay-grades',
    );
    const currencyHttp = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/admin/pay-grades/${props.payGradeId}/currencies`,
    );
    const {createUniqueValidator} = useServerValidation(http);
    const payGradeUniqueValidation = createUniqueValidator('PayGrade', 'name', {
      entityId: props.payGradeId,
    });
    return {
      http,
      currencyHttp,
      payGradeUniqueValidation,
    };
  },

  data() {
    return {
      isLoading: false,
      existingCurrencyId: null,
      currencyLabel: '',
      allowedCurrencies: [],
      grade: {
        id: '',
        name: '',
        currencyId: null,
        minSalary: '',
        maxSalary: '',
      },
      rules: {
        name: [
          required,
          this.payGradeUniqueValidation,
          shouldNotExceedCharLength(50),
        ],
        currencyId: [required],
        minSalary: [
          maxCurrency(1000000000),
          digitsOnlyWithTwoDecimalPoints,
          minValueShouldBeLowerThanMaxValue(
            () => this.grade.maxSalary,
            this.$t('admin.should_be_lower_than_maximum_salary'),
          ),
        ],
        maxSalary: [
          maxCurrency(1000000000),
          digitsOnlyWithTwoDecimalPoints,
          maxValueShouldBeGreaterThanMinValue(
            () => this.grade.minSalary,
            this.$t('admin.should_be_higher_than_minimum_salary'),
          ),
        ],
      },
    };
  },

  created() {
    this.isLoading = true;
    Promise.all([
      this.http.get(this.payGradeId),
      this.currencyHttp.getAll({limit: 0}),
      this.currencyHttp.request({
        method: 'GET',
        url: `/api/v2/admin/pay-grades/${this.payGradeId}/currencies/allowed`,
        params: {limit: 0},
      }),
    ])
      .then(([gradeResponse, currenciesResponse, allowedResponse]) => {
        const {data} = gradeResponse.data;
        this.grade.id = data.id;
        this.grade.name = data.name;

        const currencies = currenciesResponse.data?.data || [];
        if (currencies.length > 0) {
          const primary = currencies[0];
          this.existingCurrencyId = primary.currencyType.id;
          this.currencyLabel = primary.currencyType.name;
          this.grade.minSalary =
            primary.minSalary === null || primary.minSalary === undefined
              ? ''
              : String(primary.minSalary);
          this.grade.maxSalary =
            primary.maxSalary === null || primary.maxSalary === undefined
              ? ''
              : String(primary.maxSalary);
        }

        this.allowedCurrencies = (allowedResponse.data?.data || []).map(
          (item) => ({
            id: item.id,
            label: item.id + ' - ' + item.name,
          }),
        );
      })
      .finally(() => {
        this.isLoading = false;
      });
  },

  methods: {
    onSave() {
      this.isLoading = true;
      const minSalary =
        this.grade.minSalary === '' || this.grade.minSalary === null
          ? null
          : Number(this.grade.minSalary);
      const maxSalary =
        this.grade.maxSalary === '' || this.grade.maxSalary === null
          ? null
          : Number(this.grade.maxSalary);

      this.http
        .update(this.payGradeId, {
          name: this.grade.name,
        })
        .then(() => {
          if (this.existingCurrencyId) {
            return this.currencyHttp.update(this.existingCurrencyId, {
              minSalary,
              maxSalary,
            });
          }
          return this.currencyHttp.create({
            currencyId: this.grade.currencyId?.id || this.grade.currencyId,
            minSalary,
            maxSalary,
          });
        })
        .then(() => {
          return this.$toast.updateSuccess();
        })
        .then(() => {
          reloadPage();
        })
        .catch(() => {
          this.isLoading = false;
        });
    },
    onCancel() {
      navigate('/admin/viewPayGrades');
    },
  },
};
</script>
