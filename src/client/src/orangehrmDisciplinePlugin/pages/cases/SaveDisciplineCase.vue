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
            ? $t('discipline.edit_case')
            : $t('discipline.add_case')
        }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item v-if="!myCase">
              <employee-autocomplete
                v-model="caseData.employee"
                required
                :rules="rules.employee"
                :label="$t('general.employee_name')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="caseData.caseType"
                type="select"
                required
                :label="$t('discipline.case_type')"
                :options="caseTypeOptions"
                :rules="rules.caseType"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="caseData.subject"
                required
                :label="$t('discipline.case_subject')"
                :rules="rules.subject"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="showComplaintSource">
              <oxd-input-field
                v-model="caseData.complaintSource"
                type="select"
                required
                :label="$t('discipline.complaint_source')"
                :options="complaintSourceOptions"
                :rules="rules.complaintSource"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="caseData.incidentDate"
                :label="$t('discipline.incident_date')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="caseData.status"
                type="select"
                :label="$t('general.status')"
                :options="statusOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="caseData.severity"
                type="select"
                :label="$t('discipline.severity')"
                :options="severityOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="caseData.description"
                type="textarea"
                :label="$t('general.description')"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="caseData.details"
                type="textarea"
                :label="$t('discipline.more_details')"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="!myCase" class=" --span-column-2">
              <oxd-input-field
                v-model="caseData.managerNotes"
                type="textarea"
                :label="$t('discipline.manager_notes')"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="!myCase" class=" --span-column-2">
              <oxd-input-field
                v-model="caseData.actionPlan"
                type="textarea"
                :label="$t('discipline.action_plan')"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="!myCase" class=" --span-column-2">
              <oxd-input-field
                v-model="caseData.actionTaken"
                type="textarea"
                :label="$t('discipline.action_taken')"
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
  </div>
</template>

<script>
import {navigate} from '@/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';

const defaultCase = {
  employee: null,
  caseType: null,
  subject: '',
  complaintSource: null,
  description: '',
  details: '',
  incidentDate: null,
  status: null,
  severity: null,
  managerNotes: '',
  actionPlan: '',
  actionTaken: '',
};

export default {
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  props: {
    caseId: {
      type: Number,
      required: false,
      default: null,
    },
    myCase: {
      type: Boolean,
      default: false,
    },
  },
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/discipline/cases',
    );
    return {http};
  },
  data() {
    return {
      isLoading: false,
      caseData: {...defaultCase},
      rules: {
        employee: [required],
        caseType: [required],
        subject: [required],
        complaintSource: [required],
      },
      caseTypeOptions: [
        {id: 'COMPLAINT', label: this.$t('discipline.complaint')},
        {
          id: 'DISCIPLINARY',
          label: this.$t('discipline.disciplinary_action'),
        },
      ],
      complaintSourceOptions: [
        {
          id: 'TEAM_MEMBER',
          label: this.$t('discipline.source_team_member'),
        },
        {id: 'CLIENT', label: this.$t('discipline.source_client')},
        {
          id: 'GENERAL_PUBLIC',
          label: this.$t('discipline.source_general_public'),
        },
        {id: 'OTHER', label: this.$t('discipline.source_other')},
      ],
      statusOptions: [
        {id: 'OPEN', label: this.$t('discipline.open')},
        {id: 'UNDER_REVIEW', label: this.$t('discipline.under_review')},
        {id: 'RESOLVED', label: this.$t('discipline.resolved')},
        {id: 'CLOSED', label: this.$t('discipline.closed')},
      ],
      severityOptions: [
        {id: 'LOW', label: this.$t('discipline.low')},
        {id: 'MEDIUM', label: this.$t('discipline.medium')},
        {id: 'HIGH', label: this.$t('discipline.high')},
        {id: 'CRITICAL', label: this.$t('discipline.critical')},
      ],
    };
  },
  computed: {
    isEdit() {
      return this.caseId !== null;
    },
    showComplaintSource() {
      return this.caseData.caseType?.id === 'COMPLAINT';
    },
  },
  beforeMount() {
    if (this.isEdit) {
      this.isLoading = true;
      this.http
        .get(this.caseId)
        .then((response) => {
          const {data} = response.data;
          this.caseData.employee = {
            id: data.employee.empNumber,
            label: `${data.employee.firstName} ${data.employee.lastName}`,
            isPastEmployee: !!data.employee.terminationId,
          };
          this.caseData.caseType = this.caseTypeOptions.find(
            (o) => o.id === data.caseType,
          );
          this.caseData.subject = data.subject;
          this.caseData.complaintSource = this.complaintSourceOptions.find(
            (o) => o.id === data.complaintSource,
          );
          this.caseData.description = data.description;
          this.caseData.details = data.details;
          this.caseData.incidentDate = data.incidentDate;
          this.caseData.status = this.statusOptions.find(
            (o) => o.id === data.status,
          );
          this.caseData.severity = this.severityOptions.find(
            (o) => o.id === data.severity,
          );
          this.caseData.managerNotes = data.managerNotes;
          this.caseData.actionPlan = data.actionPlan;
          this.caseData.actionTaken = data.actionTaken;
        })
        .finally(() => {
          this.isLoading = false;
        });
    } else {
      this.caseData.status = this.statusOptions[0];
      this.caseData.caseType = this.myCase
        ? this.caseTypeOptions[0]
        : null;
    }
  },
  methods: {
    onCancel() {
      navigate(
        this.myCase
          ? '/discipline/viewMyDisciplineCases'
          : '/discipline/viewDisciplineCases',
      );
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        empNumber: this.myCase
          ? undefined
          : this.caseData.employee?.id,
        caseType: this.caseData.caseType?.id,
        subject: this.caseData.subject,
        complaintSource: this.showComplaintSource
          ? this.caseData.complaintSource?.id || null
          : null,
        description: this.caseData.description || null,
        details: this.caseData.details || null,
        incidentDate: this.caseData.incidentDate || null,
        status: this.caseData.status?.id || 'OPEN',
        severity: this.caseData.severity?.id || null,
        managerNotes: this.caseData.managerNotes || null,
        actionPlan: this.caseData.actionPlan || null,
        actionTaken: this.caseData.actionTaken || null,
      };
      const request = this.isEdit
        ? this.http.update(this.caseId, payload)
        : this.http.create(payload);
      request
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .then(() => this.onCancel())
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
