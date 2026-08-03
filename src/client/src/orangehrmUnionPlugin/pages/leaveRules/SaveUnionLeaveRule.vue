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
          ruleId ? $t('union.edit_leave_rule') : $t('union.add_leave_rule')
        }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <union-dropdown v-model="form.union" />
            </oxd-grid-item>
            <oxd-grid-item>
              <leave-type-dropdown
                v-model="form.leaveType"
                :eligible-only="false"
                :rules="[required]"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.minYears"
                required
                :label="$t('union.min_years')"
                :rules="[required, validNumber]"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.maxYears"
                :label="$t('union.max_years')"
                :rules="[validNumber]"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.entitlementDays"
                required
                :label="$t('union.entitlement_days')"
                :rules="[required, validNumber]"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
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
import {navigate} from '@/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {
  required,
  digitsOnlyWithDecimalPoint,
} from '@/core/util/validation/rules';
import UnionDropdown from '@/orangehrmUnionPlugin/components/UnionDropdown';
import LeaveTypeDropdown from '@/orangehrmLeavePlugin/components/LeaveTypeDropdown';

const defaultForm = {
  union: null,
  leaveType: null,
  minYears: '0',
  maxYears: null,
  entitlementDays: null,
};

export default {
  components: {
    'union-dropdown': UnionDropdown,
    'leave-type-dropdown': LeaveTypeDropdown,
  },
  props: {
    ruleId: {type: Number, default: null},
  },
  setup() {
    return {
      required,
      validNumber: digitsOnlyWithDecimalPoint,
      http: new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/union/leave-rules',
      ),
    };
  },
  data() {
    return {
      isLoading: false,
      form: {...defaultForm},
    };
  },
  beforeMount() {
    if (!this.ruleId) {
      return;
    }
    this.isLoading = true;
    this.http
      .get(this.ruleId)
      .then(({data}) => {
        const row = data.data;
        this.form.union = row.union
          ? {id: row.union.id, label: row.union.name}
          : null;
        this.form.leaveType = row.leaveType
          ? {id: row.leaveType.id, label: row.leaveType.name}
          : null;
        this.form.minYears = String(row.minYears);
        this.form.maxYears =
          row.maxYears === null || row.maxYears === undefined
            ? null
            : String(row.maxYears);
        this.form.entitlementDays = String(row.entitlementDays);
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      this.isLoading = true;
      const payload = {
        unionId: this.form.union?.id ?? null,
        leaveTypeId: this.form.leaveType?.id,
        minYears: Number(this.form.minYears),
        maxYears:
          this.form.maxYears === null || this.form.maxYears === ''
            ? null
            : Number(this.form.maxYears),
        entitlementDays: Number(this.form.entitlementDays),
      };
      const req = this.ruleId
        ? this.http.update(this.ruleId, payload)
        : this.http.create(payload);
      req
        .then(() =>
          this.ruleId ? this.$toast.updateSuccess() : this.$toast.saveSuccess(),
        )
        .then(this.onCancel)
        .finally(() => {
          this.isLoading = false;
        });
    },
    onCancel() {
      navigate('/union/viewUnionLeaveRules');
    },
  },
};
</script>
