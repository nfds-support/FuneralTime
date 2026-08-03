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
          assignmentId
            ? $t('union.assign_employee_union')
            : $t('union.assign_employee_union')
        }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <employee-autocomplete
                v-model="form.employee"
                required
                :rules="[required]"
                :label="$t('general.employee_name')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <union-dropdown
                v-model="form.union"
                required
                :rules="[required]"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="form.seniorityDate"
                required
                :label="$t('union.seniority_date')"
                :rules="[required]"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.seniorityRank"
                :label="$t('union.seniority_rank')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.primary"
                type="switch"
                :label="$t('union.primary_union')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="form.startDate"
                :label="$t('general.start_date')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="form.endDate"
                :label="$t('general.end_date')"
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
import {required} from '@/core/util/validation/rules';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';
import UnionDropdown from '@/orangehrmUnionPlugin/components/UnionDropdown';

const defaultForm = {
  employee: null,
  union: null,
  seniorityDate: '',
  seniorityRank: null,
  primary: true,
  startDate: null,
  endDate: null,
};

export default {
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
    'union-dropdown': UnionDropdown,
  },
  props: {
    assignmentId: {type: Number, default: null},
  },
  setup() {
    return {
      required,
      http: new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/union/employee-unions',
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
    if (!this.assignmentId) {
      return;
    }
    this.isLoading = true;
    this.http
      .get(this.assignmentId)
      .then(({data}) => {
        const row = data.data;
        this.form.employee = {
          id: row.employee.empNumber,
          label: `${row.employee.firstName} ${row.employee.lastName}`.trim(),
        };
        this.form.union = row.union
          ? {id: row.union.id, label: row.union.name}
          : null;
        this.form.seniorityDate = row.seniorityDate;
        this.form.seniorityRank = row.seniorityRank;
        this.form.primary = !!row.primary;
        this.form.startDate = row.startDate;
        this.form.endDate = row.endDate;
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      this.isLoading = true;
      const payload = {
        empNumber: this.form.employee?.id,
        unionId: this.form.union?.id,
        seniorityDate: this.form.seniorityDate,
        seniorityRank:
          this.form.seniorityRank === null || this.form.seniorityRank === ''
            ? null
            : Number(this.form.seniorityRank),
        primary: !!this.form.primary,
        startDate: this.form.startDate || null,
        endDate: this.form.endDate || null,
      };
      const req = this.assignmentId
        ? this.http.update(this.assignmentId, payload)
        : this.http.create(payload);
      req
        .then(() =>
          this.assignmentId
            ? this.$toast.updateSuccess()
            : this.$toast.saveSuccess(),
        )
        .then(this.onCancel)
        .finally(() => {
          this.isLoading = false;
        });
    },
    onCancel() {
      navigate('/union/viewEmployeeUnions');
    },
  },
};
</script>
