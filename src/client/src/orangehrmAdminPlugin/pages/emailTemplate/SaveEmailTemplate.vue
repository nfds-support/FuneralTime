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
        {{ $t('admin.add_email_template') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.email"
                type="select"
                :label="$t('admin.email_type')"
                :options="emailOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.emailName"
                :label="$t('admin.new_email_type')"
                :placeholder="$t('admin.new_email_type_hint')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.locale"
                :label="$t('admin.locale')"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.recipientRole"
                :label="$t('admin.recipient_role')"
                :rules="rules.required"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.performerRole"
                :label="$t('admin.performer_role')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-row>
          <oxd-input-field
            v-model="form.subject"
            :label="$t('admin.email_subject')"
            :rules="rules.required"
            required
          />
        </oxd-form-row>
        <oxd-form-row>
          <tinymce-input
            v-model="form.body"
            :label="$t('admin.email_body')"
            :rules="rules.required"
            :placeholder="$t('admin.email_template_twig_hint')"
            :height="360"
            required
          />
          <oxd-text tag="p" class="orangehrm-input-hint">
            {{ $t('admin.email_template_twig_hint') }}
          </oxd-text>
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
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';

export default {
  data() {
    return {
      isLoading: false,
      emailOptions: [],
      form: {
        email: null,
        emailName: '',
        locale: 'en_US',
        recipientRole: 'ess',
        performerRole: '',
        subject: '',
        body: '',
      },
      rules: {required: [required]},
      http: new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/admin/email-templates',
      ),
      emailsHttp: new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/admin/emails',
      ),
    };
  },
  beforeMount() {
    this.isLoading = true;
    this.emailsHttp
      .getAll()
      .then(({data}) => {
        this.emailOptions = (data.data || []).map((item) => ({
          id: item.id,
          label: item.name,
        }));
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      if (!this.form.emailName && !this.form.email?.id) {
        this.$toast.error({
          title: this.$t('general.error'),
          message: this.$t('admin.email_type_required'),
        });
        return;
      }
      this.isLoading = true;
      const payload = {
        locale: this.form.locale,
        recipientRole: this.form.recipientRole,
        performerRole: this.form.performerRole || null,
        subject: this.form.subject,
        body: this.form.body,
      };
      if (this.form.emailName) {
        payload.emailName = this.form.emailName;
      } else {
        payload.emailId = this.form.email.id;
      }
      this.http
        .create(payload)
        .then(() => this.$toast.saveSuccess())
        .then(() => this.onCancel())
        .catch(() => {
          this.isLoading = false;
        });
    },
    onCancel() {
      navigate('/admin/viewEmailTemplates');
    },
  },
};
</script>
