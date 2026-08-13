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
          isEdit
            ? $t('performance.edit_monthly_assessment')
            : $t('performance.start_assessment')
        }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave(false)">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item v-if="mode === 'manager' && !isEdit">
              <employee-autocomplete
                v-model="form.employee"
                required
                :rules="rules.employee"
                :label="$t('general.employee_name')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.periodMonth"
                type="select"
                required
                :label="$t('performance.month')"
                :options="monthOptions"
                :rules="rules.periodMonth"
                :disabled="isEdit"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.periodYear"
                type="select"
                required
                :label="$t('performance.year')"
                :options="yearOptions"
                :rules="rules.periodYear"
                :disabled="isEdit"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('performance.self_assessment') }}
        </oxd-text>
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.employeeOverallRating"
                type="select"
                :label="$t('performance.overall_rating')"
                :options="ratingOptions"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.employeeEngagementRating"
                type="select"
                :label="$t('performance.engagement_rating')"
                :options="ratingOptions"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.employeeAccomplishments"
                type="textarea"
                :label="$t('performance.accomplishments')"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.employeeImprovements"
                type="textarea"
                :label="$t('performance.areas_to_improve')"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.employeeGoals"
                type="textarea"
                :label="$t('performance.goals_next_month')"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.employeeSupportNeeded"
                type="textarea"
                :label="$t('performance.support_needed')"
                :disabled="!canEditSelf"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('performance.manager_assessment') }}
        </oxd-text>
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.managerOverallRating"
                type="select"
                :label="$t('performance.overall_rating')"
                :options="ratingOptions"
                :disabled="!canEditManager"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.managerStrengths"
                type="textarea"
                :label="$t('performance.strengths')"
                :disabled="!canEditManagerCore"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.managerImprovements"
                type="textarea"
                :label="$t('performance.areas_to_improve')"
                :disabled="!canEditManagerCore"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.managerGoalsSupport"
                type="textarea"
                :label="$t('performance.goals_support')"
                :disabled="!canEditManagerCore"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.managerFollowUpNotes"
                type="textarea"
                :label="$t('performance.follow_up_notes')"
                :disabled="!canEditFollowUp"
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
          <oxd-button
            v-if="canSubmit"
            display-type="ghost"
            :label="$t('performance.save_draft')"
            type="button"
            @click="onSave(false)"
          />
          <oxd-button
            v-if="canSubmit"
            display-type="secondary"
            :label="$t('performance.submit_assessment')"
            type="button"
            @click="onSave(true)"
          />
          <submit-button v-if="!canSubmit && canEditFollowUp" />
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

const now = new Date();

export default {
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  props: {
    assessmentId: {
      type: Number,
      default: null,
    },
    mode: {
      type: String,
      default: 'self',
    },
  },
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/performance/monthly-assessments',
    );
    return {http};
  },
  data() {
    const years = [];
    for (let y = now.getFullYear() - 1; y <= now.getFullYear() + 1; y++) {
      years.push({id: y, label: String(y)});
    }
    return {
      isLoading: false,
      employeeSubmitted: false,
      managerSubmitted: false,
      form: {
        employee: null,
        periodMonth: null,
        periodYear: null,
        employeeOverallRating: null,
        employeeEngagementRating: null,
        employeeAccomplishments: '',
        employeeImprovements: '',
        employeeGoals: '',
        employeeSupportNeeded: '',
        managerOverallRating: null,
        managerStrengths: '',
        managerImprovements: '',
        managerGoalsSupport: '',
        managerFollowUpNotes: '',
      },
      rules: {
        employee: [required],
        periodMonth: [required],
        periodYear: [required],
      },
      monthOptions: [
        {id: 1, label: 'January'},
        {id: 2, label: 'February'},
        {id: 3, label: 'March'},
        {id: 4, label: 'April'},
        {id: 5, label: 'May'},
        {id: 6, label: 'June'},
        {id: 7, label: 'July'},
        {id: 8, label: 'August'},
        {id: 9, label: 'September'},
        {id: 10, label: 'October'},
        {id: 11, label: 'November'},
        {id: 12, label: 'December'},
      ],
      yearOptions: years,
      ratingOptions: [
        {id: 1, label: '1'},
        {id: 2, label: '2'},
        {id: 3, label: '3'},
        {id: 4, label: '4'},
        {id: 5, label: '5'},
      ],
    };
  },
  computed: {
    isEdit() {
      return this.assessmentId !== null;
    },
    canEditSelf() {
      return this.mode === 'self' && !this.employeeSubmitted;
    },
    canEditManagerCore() {
      return this.mode === 'manager' && !this.managerSubmitted;
    },
    canEditManager() {
      return this.canEditManagerCore;
    },
    canEditFollowUp() {
      return this.mode === 'manager';
    },
    canSubmit() {
      if (this.mode === 'self') {
        return !this.employeeSubmitted;
      }
      return !this.managerSubmitted;
    },
  },
  beforeMount() {
    if (this.isEdit) {
      this.isLoading = true;
      this.http
        .get(this.assessmentId)
        .then((response) => {
          const {data} = response.data;
          this.employeeSubmitted = !!data.employeeSubmittedAt;
          this.managerSubmitted = !!data.managerSubmittedAt;
          this.form.employee = {
            id: data.employee.empNumber,
            label: `${data.employee.firstName} ${data.employee.lastName}`,
            isPastEmployee: !!data.employee.terminationId,
          };
          this.form.periodMonth = this.monthOptions.find(
            (o) => o.id === data.periodMonth,
          );
          this.form.periodYear = this.yearOptions.find(
            (o) => o.id === data.periodYear,
          ) || {id: data.periodYear, label: String(data.periodYear)};
          this.form.employeeOverallRating = this.ratingOptions.find(
            (o) => o.id === data.employeeOverallRating,
          );
          this.form.employeeEngagementRating = this.ratingOptions.find(
            (o) => o.id === data.employeeEngagementRating,
          );
          this.form.employeeAccomplishments = data.employeeAccomplishments;
          this.form.employeeImprovements = data.employeeImprovements;
          this.form.employeeGoals = data.employeeGoals;
          this.form.employeeSupportNeeded = data.employeeSupportNeeded;
          this.form.managerOverallRating = this.ratingOptions.find(
            (o) => o.id === data.managerOverallRating,
          );
          this.form.managerStrengths = data.managerStrengths;
          this.form.managerImprovements = data.managerImprovements;
          this.form.managerGoalsSupport = data.managerGoalsSupport;
          this.form.managerFollowUpNotes = data.managerFollowUpNotes;
        })
        .finally(() => {
          this.isLoading = false;
        });
    } else {
      this.form.periodMonth = this.monthOptions.find(
        (o) => o.id === now.getMonth() + 1,
      );
      this.form.periodYear = this.yearOptions.find(
        (o) => o.id === now.getFullYear(),
      );
    }
  },
  methods: {
    onCancel() {
      navigate(
        this.mode === 'manager'
          ? '/performance/viewTeamMonthlyAssessments'
          : '/performance/viewMyMonthlyAssessments',
      );
    },
    buildPayload(submit) {
      const payload = {
        periodYear: this.form.periodYear?.id,
        periodMonth: this.form.periodMonth?.id,
        employeeOverallRating: this.form.employeeOverallRating?.id || null,
        employeeEngagementRating:
          this.form.employeeEngagementRating?.id || null,
        employeeAccomplishments: this.form.employeeAccomplishments || null,
        employeeImprovements: this.form.employeeImprovements || null,
        employeeGoals: this.form.employeeGoals || null,
        employeeSupportNeeded: this.form.employeeSupportNeeded || null,
        managerOverallRating: this.form.managerOverallRating?.id || null,
        managerStrengths: this.form.managerStrengths || null,
        managerImprovements: this.form.managerImprovements || null,
        managerGoalsSupport: this.form.managerGoalsSupport || null,
        managerFollowUpNotes: this.form.managerFollowUpNotes || null,
        submit: !!submit,
        submitAs: this.mode === 'manager' ? 'manager' : 'employee',
      };
      if (this.mode === 'manager' && this.form.employee?.id) {
        payload.empNumber = this.form.employee.id;
      }
      return payload;
    },
    onSave(submit) {
      this.isLoading = true;
      const payload = this.buildPayload(submit);
      const request = this.isEdit
        ? this.http.update(this.assessmentId, payload)
        : this.http.create(payload);
      request
        .then(() => this.$toast.saveSuccess())
        .then(() => this.onCancel())
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
