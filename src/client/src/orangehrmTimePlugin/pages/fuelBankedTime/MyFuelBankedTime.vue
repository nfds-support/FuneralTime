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
        {{ $t('time.apply_fuel_for_banked_time') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSubmit">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                :model-value="eligibility.bankedHours?.toFixed(2)"
                :label="$t('time.banked_hours_available')"
                disabled
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                :model-value="
                  eligibility.hourlyRate != null
                    ? Number(eligibility.hourlyRate).toFixed(2)
                    : ''
                "
                :label="$t('time.hourly_rate')"
                disabled
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                :model-value="hoursToDeduct"
                :label="$t('time.hours_to_deduct')"
                disabled
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-text
          v-if="!eligibility.enabled"
          class="orangehrm-input-hint"
          tag="p"
        >
          {{ $t('time.fuel_for_banked_time_disabled') }}
        </oxd-text>
        <oxd-text
          v-else-if="eligibility.hourlyRate == null"
          class="orangehrm-input-hint"
          tag="p"
        >
          {{ $t('time.fuel_hourly_rate_missing') }}
        </oxd-text>
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.amount"
                :label="$t('time.fuel_amount')"
                :rules="rules.amount"
                :disabled="!canApply"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.comment"
                :label="$t('general.comment')"
                type="textarea"
                :disabled="!canApply"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <oxd-button
            display-type="secondary"
            :label="$t('general.submit')"
            type="submit"
            :disabled="!canApply"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
    <br />
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('time.my_fuel_for_banked_time') }}
        </oxd-text>
      </div>
      <table-header
        :selected="0"
        :total="total"
        :loading="isLoadingList"
      ></table-header>
      <div class="orangehrm-container">
        <oxd-card-table
          :headers="headers"
          :items="items?.data"
          :clickable="false"
          :loading="isLoadingList"
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
  </div>
</template>

<script>
import {computed, onBeforeMount, reactive, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import usei18n from '@/core/util/composable/usei18n';
import {required, digitsOnlyWithDecimalPoint} from '@/core/util/validation/rules';
import useToast from '@/core/util/composable/useToast';

export default {
  name: 'MyFuelBankedTime',
  setup() {
    const {$t} = usei18n();
    const {toastSuccess, toastError} = useToast();
    const isLoading = ref(false);
    const eligibility = reactive({
      enabled: false,
      hourlyRate: null,
      bankedHours: 0,
      hoursPerDay: 8,
    });
    const form = reactive({
      amount: '',
      comment: '',
    });

    const eligibilityHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/fuel-banked-time/eligibility',
    );
    const requestHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/fuel-banked-time/requests',
    );

    const normalizer = (data) =>
      data.map((item) => ({
        id: item.id,
        amount: Number(item.amount).toFixed(2),
        hourlyRate: Number(item.hourlyRate).toFixed(2),
        hours: Number(item.hours).toFixed(2),
        status: item.status,
        createdAt: item.createdAt,
        comment: item.comment || '',
      }));

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      response,
      isLoading: isLoadingList,
      execQuery,
    } = usePaginate(requestHttp, {normalizer});

    const canApply = computed(
      () =>
        eligibility.enabled &&
        eligibility.hourlyRate != null &&
        Number(eligibility.hourlyRate) > 0 &&
        Number(eligibility.bankedHours) > 0,
    );

    const hoursToDeduct = computed(() => {
      const amount = parseFloat(form.amount);
      const rate = Number(eligibility.hourlyRate);
      if (!amount || !rate || amount <= 0 || rate <= 0) {
        return '0.00';
      }
      return (amount / rate).toFixed(2);
    });

    const rules = {
      amount: [required, digitsOnlyWithDecimalPoint],
    };

    const headers = computed(() => [
      {name: 'createdAt', title: $t('general.date'), style: {flex: 1}},
      {name: 'amount', title: $t('time.fuel_amount'), style: {flex: 1}},
      {name: 'hourlyRate', title: $t('time.hourly_rate'), style: {flex: 1}},
      {name: 'hours', title: $t('time.hours_to_deduct'), style: {flex: 1}},
      {name: 'status', title: $t('general.status'), style: {flex: 1}},
      {name: 'comment', title: $t('general.comment'), style: {flex: 1}},
    ]);

    const loadEligibility = async () => {
      const {data} = await eligibilityHttp.get(0);
      Object.assign(eligibility, data.data);
    };

    const onSubmit = async () => {
      isLoading.value = true;
      try {
        await requestHttp.create({
          amount: parseFloat(form.amount),
          comment: form.comment || null,
        });
        form.amount = '';
        form.comment = '';
        toastSuccess({
          title: $t('general.success'),
          message: $t('general.successfully_saved'),
        });
        await loadEligibility();
        await execQuery();
      } catch (error) {
        const message =
          error?.response?.data?.error?.message ||
          $t('general.unexpected_error');
        toastError({title: $t('general.error'), message});
      } finally {
        isLoading.value = false;
      }
    };

    onBeforeMount(async () => {
      isLoading.value = true;
      try {
        await loadEligibility();
      } finally {
        isLoading.value = false;
      }
    });

    return {
      isLoading,
      eligibility,
      form,
      rules,
      canApply,
      hoursToDeduct,
      onSubmit,
      headers,
      showPaginator,
      currentPage,
      total,
      pages,
      items: response,
      isLoadingList,
    };
  },
};
</script>
