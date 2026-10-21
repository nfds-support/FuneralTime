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
        {{ $t('general.add_membership') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="membership.name"
                :label="$t('general.name')"
                :rules="rules.name"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="membership.duesMode"
                type="select"
                :label="$t('admin.dues_mode')"
                :options="duesModeOptions"
                :rules="rules.duesMode"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <template v-if="isRateWeekly">
          <oxd-divider />
          <oxd-form-row>
            <oxd-grid :cols="3" class="orangehrm-full-width-grid">
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.duesHourlyMultiplier"
                  :label="$t('admin.dues_hourly_multiplier')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.duesWeeklyFlatFee"
                  :label="$t('admin.dues_weekly_flat_fee')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-text tag="p" class="orangehrm-input-hint">
                  {{ $t('admin.help_membership_dues_formula') }}
                </oxd-text>
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.initiationFeeFullTime"
                  :label="$t('admin.initiation_fee_full_time')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.initiationWeeklyMaxFullTime"
                  :label="$t('admin.initiation_weekly_max_full_time')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.initiationFeePartTime"
                  :label="$t('admin.initiation_fee_part_time')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
              <oxd-grid-item>
                <oxd-input-field
                  v-model="membership.initiationWeeklyMaxPartTime"
                  :label="$t('admin.initiation_weekly_max_part_time')"
                  :rules="rules.number"
                  required
                />
              </oxd-grid-item>
            </oxd-grid>
          </oxd-form-row>
        </template>

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
  </div>
</template>

<script>
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
  digitsOnlyWithDecimalPoint,
} from '@ohrm/core/util/validation/rules';
import useServerValidation from '@/core/util/composable/useServerValidation';

export default {
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/memberships',
    );
    const {createUniqueValidator} = useServerValidation(http);
    const membershipUniqueValidation = createUniqueValidator(
      'Membership',
      'name',
    );

    return {
      http,
      membershipUniqueValidation,
    };
  },
  data() {
    return {
      isLoading: false,
      membership: {
        id: '',
        name: '',
        duesMode: null,
        duesHourlyMultiplier: '0.6',
        duesWeeklyFlatFee: '0.25',
        initiationFeeFullTime: '40',
        initiationFeePartTime: '25',
        initiationWeeklyMaxFullTime: '10',
        initiationWeeklyMaxPartTime: '5',
      },
      rules: {
        name: [
          required,
          shouldNotExceedCharLength(50),
          this.membershipUniqueValidation,
        ],
        duesMode: [required],
        number: [required, digitsOnlyWithDecimalPoint],
      },
    };
  },
  computed: {
    duesModeOptions() {
      return [
        {id: 'flat', label: this.$t('admin.dues_mode_flat')},
        {id: 'rate_weekly', label: this.$t('admin.dues_mode_rate_weekly')},
      ];
    },
    isRateWeekly() {
      return this.membership.duesMode?.id === 'rate_weekly';
    },
  },
  created() {
    this.membership.duesMode = this.duesModeOptions[0];
  },
  methods: {
    buildPayload() {
      const payload = {
        name: this.membership.name,
        duesMode: this.membership.duesMode?.id || 'flat',
      };
      if (payload.duesMode === 'rate_weekly') {
        payload.duesHourlyMultiplier = Number(
          this.membership.duesHourlyMultiplier,
        );
        payload.duesWeeklyFlatFee = Number(this.membership.duesWeeklyFlatFee);
        payload.initiationFeeFullTime = Number(
          this.membership.initiationFeeFullTime,
        );
        payload.initiationFeePartTime = Number(
          this.membership.initiationFeePartTime,
        );
        payload.initiationWeeklyMaxFullTime = Number(
          this.membership.initiationWeeklyMaxFullTime,
        );
        payload.initiationWeeklyMaxPartTime = Number(
          this.membership.initiationWeeklyMaxPartTime,
        );
      }
      return payload;
    },
    onSave() {
      this.isLoading = true;
      this.http
        .create(this.buildPayload())
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .then(() => {
          this.membership.name = '';
          this.onCancel();
        });
    },
    onCancel() {
      navigate('/admin/membership');
    },
  },
};
</script>
