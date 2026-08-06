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
        {{ $t('time.ufcw_remittance_settings') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.duesHourlyMultiplier"
                :label="$t('time.dues_hourly_multiplier')"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.duesWeeklyFlatFee"
                :label="$t('time.dues_weekly_flat_fee')"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-text tag="p" class="orangehrm-input-hint">
                {{ $t('time.help_dues_formula') }}
              </oxd-text>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.initiationFeeFullTime"
                label="Initiation fee (full-time)"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.initiationWeeklyMaxFullTime"
                label="Max weekly initiation (full-time)"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.initiationFeePartTime"
                label="Initiation fee (part-time)"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.initiationWeeklyMaxPartTime"
                label="Max weekly initiation (part-time)"
                :rules="rules.number"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.employerName"
                label="Employer"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.workLocation"
                label="Work location"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.workLocationCode"
                label="Work location code"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.unionContacts"
                label="Union contact(s)"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.membershipName"
                :label="$t('time.membership_name')"
                :rules="rules.required"
                required
              />
              <oxd-text tag="p" class="orangehrm-input-hint">
                {{ $t('time.help_membership_name') }}
              </oxd-text>
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.remittanceEmail"
                :label="$t('time.remittance_email')"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.payrollEmail"
                :label="$t('time.payroll_email')"
              />
              <oxd-text tag="p" class="orangehrm-input-hint">
                {{ $t('time.help_payroll_email') }}
              </oxd-text>
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.chequePayableTo"
                label="Cheque payable to"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.chequeAttention"
                label="Cheque attention"
                :rules="rules.required"
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
            :label="$t('general.save')"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {onBeforeMount, reactive, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import useToast from '@/core/util/composable/useToast';

const apiPath = 'api/v2/time/ufcw-remittance/config';

export default {
  name: 'UfcwRemittanceConfig',
  setup() {
    const isLoading = ref(false);
    const {success} = useToast();
    const http = new APIService(window.appGlobal.baseUrl, apiPath);
    const form = reactive({
      duesHourlyMultiplier: '0.6',
      duesWeeklyFlatFee: '0.25',
      initiationFeeFullTime: '40',
      initiationFeePartTime: '25',
      initiationWeeklyMaxFullTime: '10',
      initiationWeeklyMaxPartTime: '5',
      employerName: '',
      workLocation: '',
      workLocationCode: '',
      unionContacts: '',
      membershipName: '',
      remittanceEmail: '',
      payrollEmail: '',
      chequePayableTo: '',
      chequeAttention: '',
    });
    const rules = {
      required: [required],
      number: [
        required,
        (v) =>
          v === '' ||
          v === null ||
          !Number.isNaN(Number(v)) ||
          'Must be a number',
      ],
    };

    const load = async () => {
      isLoading.value = true;
      try {
        const {data} = await http.getAll();
        const payload = data?.data || {};
        Object.keys(form).forEach((key) => {
          if (payload[key] !== undefined && payload[key] !== null) {
            form[key] = String(payload[key]);
          }
        });
      } finally {
        isLoading.value = false;
      }
    };

    const onSave = async () => {
      isLoading.value = true;
      try {
        await http.request({
          method: 'PUT',
          data: {
            duesHourlyMultiplier: Number(form.duesHourlyMultiplier),
            duesWeeklyFlatFee: Number(form.duesWeeklyFlatFee),
            initiationFeeFullTime: Number(form.initiationFeeFullTime),
            initiationFeePartTime: Number(form.initiationFeePartTime),
            initiationWeeklyMaxFullTime: Number(form.initiationWeeklyMaxFullTime),
            initiationWeeklyMaxPartTime: Number(form.initiationWeeklyMaxPartTime),
            employerName: form.employerName,
            workLocation: form.workLocation,
            workLocationCode: form.workLocationCode,
            unionContacts: form.unionContacts,
            membershipName: form.membershipName,
            remittanceEmail: form.remittanceEmail,
            payrollEmail: form.payrollEmail,
            chequePayableTo: form.chequePayableTo,
            chequeAttention: form.chequeAttention,
          },
        });
        success({toastMessage: 'Successfully Updated'});
      } finally {
        isLoading.value = false;
      }
    };

    onBeforeMount(load);

    return {isLoading, form, rules, onSave};
  },
};
</script>
