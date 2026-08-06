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
        {{ isEdit ? $t('policy.edit_policy') : $t('policy.add_policy') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.title"
                required
                :label="$t('general.title')"
                :rules="rules.title"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.version"
                :label="$t('policy.version')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.status"
                type="select"
                :label="$t('general.status')"
                :options="statusOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.audienceType"
                type="select"
                :label="$t('policy.audience')"
                :options="audienceOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input v-model="form.effectiveDate" :label="$t('policy.effective_date')" />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input v-model="form.dueDate" :label="$t('policy.due_date')" />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.summary"
                type="textarea"
                :label="$t('policy.summary')"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.content"
                type="textarea"
                :label="$t('policy.content')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.documentUrl"
                :label="$t('policy.document_url')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.moodleCourseUrl"
                :label="$t('policy.moodle_course_url')"
              />
            </oxd-grid-item>
            <oxd-grid-item
              v-if="form.audienceType && form.audienceType.id === 'JOB_TITLE'"
              class=" --span-column-2"
            >
              <oxd-input-field
                v-model="form.jobTitles"
                type="multiselect"
                :label="$t('general.job_title')"
                :options="jobTitleOptions"
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
const defaultForm = {
  title: '',
  version: '1.0',
  summary: '',
  content: '',
  documentUrl: '',
  moodleCourseUrl: '',
  audienceType: null,
  status: null,
  effectiveDate: null,
  dueDate: null,
  jobTitles: [],
};

export default {
  props: {
    policyId: {type: Number, default: null},
  },
  data() {
    return {
      isLoading: false,
      form: {...defaultForm},
      rules: {title: [required]},
      jobTitleOptions: [],
      statusOptions: [
        {id: 'DRAFT', label: 'Draft'},
        {id: 'PUBLISHED', label: 'Published'},
        {id: 'ARCHIVED', label: 'Archived'},
      ],
      audienceOptions: [
        {id: 'ALL', label: 'All employees'},
        {id: 'JOB_TITLE', label: 'By job title'},
      ],
      http: new APIService(window.appGlobal.baseUrl, '/api/v2/policy/policies'),
      jobTitleHttp: new APIService(window.appGlobal.baseUrl, '/api/v2/admin/job-titles'),
    };
  },
  computed: {
    isEdit() {
      return this.policyId !== null;
    },
  },
  beforeMount() {
    this.form.status = this.statusOptions[0];
    this.form.audienceType = this.audienceOptions[0];
    this.jobTitleHttp.getAll({limit: 0}).then(({data}) => {
      this.jobTitleOptions = data.data.map((item) => ({
        id: item.id,
        label: item.title,
      }));
    });
    if (this.isEdit) {
      this.isLoading = true;
      this.http
        .get(this.policyId)
        .then((response) => {
          const {data} = response.data;
          this.form.title = data.title;
          this.form.version = data.version;
          this.form.summary = data.summary;
          this.form.content = data.content;
          this.form.documentUrl = data.documentUrl;
          this.form.moodleCourseUrl = data.moodleCourseUrl;
          this.form.effectiveDate = data.effectiveDate;
          this.form.dueDate = data.dueDate;
          this.form.status =
            this.statusOptions.find((o) => o.id === data.status) || null;
          this.form.audienceType =
            this.audienceOptions.find((o) => o.id === data.audienceType) || null;
          this.form.jobTitles = (data.jobTitles || []).map((jt) => ({
            id: jt.id,
            label: jt.title,
          }));
        })
        .finally(() => {
          this.isLoading = false;
        });
    }
  },
  methods: {
    onCancel() {
      navigate('/policy/viewPolicies');
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        title: this.form.title,
        version: this.form.version,
        summary: this.form.summary,
        content: this.form.content,
        documentUrl: this.form.documentUrl || null,
        moodleCourseUrl: this.form.moodleCourseUrl || null,
        audienceType: this.form.audienceType?.id,
        status: this.form.status?.id,
        effectiveDate: this.form.effectiveDate,
        dueDate: this.form.dueDate,
        jobTitleIds: (this.form.jobTitles || []).map((jt) => jt.id),
      };
      const request = this.isEdit
        ? this.http.update(this.policyId, payload)
        : this.http.create(payload);
      request
        .then(() => navigate('/policy/viewPolicies'))
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
