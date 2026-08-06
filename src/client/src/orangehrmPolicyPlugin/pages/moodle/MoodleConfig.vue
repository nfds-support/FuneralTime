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
        {{ $t('policy.moodle_settings') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.baseUrl"
                :label="$t('policy.moodle_base_url')"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.webserviceToken"
                type="password"
                :label="$t('policy.moodle_token')"
                :placeholder="tokenPlaceholder"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.syncEnabled"
                type="switch"
                :label="$t('policy.moodle_sync_enabled')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-text tag="h6">{{ $t('policy.cohort_maps') }}</oxd-text>
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="mapForm.jobTitle"
                type="select"
                :label="$t('general.job_title')"
                :options="jobTitleOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="mapForm.moodleCohortId"
                :label="$t('policy.moodle_cohort_id')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="mapForm.moodleCohortName"
                :label="$t('policy.moodle_cohort_name')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-button
          :label="$t('policy.add_cohort_map')"
          display-type="secondary"
          @click="onAddMap"
        />
        <div class="orangehrm-container">
          <oxd-card-table
            :items="maps"
            :headers="mapHeaders"
            :selectable="false"
            :loading="mapsLoading"
            row-decorator="oxd-table-decorator-card"
          />
        </div>
        <oxd-divider />
        <oxd-form-actions>
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

export default {
  data() {
    return {
      isLoading: false,
      mapsLoading: false,
      tokenPlaceholder: '',
      form: {baseUrl: '', webserviceToken: '', syncEnabled: false},
      mapForm: {jobTitle: null, moodleCohortId: '', moodleCohortName: ''},
      maps: [],
      jobTitleOptions: [],
      configHttp: new APIService(window.appGlobal.baseUrl, '/api/v2/policy/moodle/config'),
      mapHttp: new APIService(window.appGlobal.baseUrl, '/api/v2/policy/moodle/cohort-maps'),
      jobTitleHttp: new APIService(window.appGlobal.baseUrl, '/api/v2/admin/job-titles'),
      mapHeaders: [
        {name: 'jobTitle', title: 'Job Title', style: {flex: 1}},
        {name: 'moodleCohortId', title: 'Cohort ID', style: {flex: 0.5}},
        {name: 'moodleCohortName', title: 'Cohort Name', style: {flex: 0.8}},
        {
          name: 'actions',
          slot: 'action',
          title: 'Actions',
          style: {flex: 0.4},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            delete: {
              onClick: (item) => this.onDeleteMap(item.id),
              props: {name: 'trash'},
            },
          },
        },
      ],
    };
  },
  beforeMount() {
    this.isLoading = true;
    this.configHttp
      .getAll()
      .then(({data}) => {
        const cfg = data.data;
        this.form.baseUrl = cfg.baseUrl || '';
        this.form.syncEnabled = !!cfg.syncEnabled;
        this.tokenPlaceholder = cfg.webserviceTokenSet ? '•••••••• (saved)' : '';
      })
      .finally(() => {
        this.isLoading = false;
      });
    this.jobTitleHttp.getAll({limit: 0}).then(({data}) => {
      this.jobTitleOptions = data.data.map((item) => ({
        id: item.id,
        label: item.title,
      }));
    });
    this.loadMaps();
  },
  methods: {
    loadMaps() {
      this.mapsLoading = true;
      this.mapHttp
        .getAll({limit: 0})
        .then(({data}) => {
          this.maps = data.data.map((item) => ({
            id: item.id,
            jobTitle: item.jobTitle?.title,
            moodleCohortId: item.moodleCohortId,
            moodleCohortName: item.moodleCohortName,
          }));
        })
        .finally(() => {
          this.mapsLoading = false;
        });
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        baseUrl: this.form.baseUrl,
        syncEnabled: !!this.form.syncEnabled,
      };
      if (this.form.webserviceToken) {
        payload.webserviceToken = this.form.webserviceToken;
      }
      this.configHttp
        .request({method: 'PUT', data: payload})
        .then(() => navigate('/policy/viewPolicies'))
        .finally(() => {
          this.isLoading = false;
        });
    },
    onAddMap() {
      if (!this.mapForm.jobTitle || !this.mapForm.moodleCohortId) return;
      this.mapHttp
        .create({
          jobTitleId: this.mapForm.jobTitle.id,
          moodleCohortId: parseInt(this.mapForm.moodleCohortId, 10),
          moodleCohortName: this.mapForm.moodleCohortName || null,
        })
        .then(() => {
          this.mapForm = {jobTitle: null, moodleCohortId: '', moodleCohortName: ''};
          this.loadMaps();
        });
    },
    onDeleteMap(id) {
      this.mapHttp.deleteAll({ids: [id]}).then(() => this.loadMaps());
    },
    onCancel() {
      navigate('/policy/viewPolicies');
    },
  },
};
</script>
