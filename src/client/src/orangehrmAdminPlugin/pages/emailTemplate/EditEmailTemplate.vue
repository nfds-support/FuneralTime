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
        {{ $t('admin.edit_email_template') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.emailName"
                :label="$t('admin.email_type')"
                :disabled="true"
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
          <oxd-button
            v-if="form.hasCustomContent && form.hasDefaultPath"
            type="button"
            display-type="ghost"
            class="orangehrm-left-space"
            :label="$t('admin.reset_to_default')"
            @click="onReset"
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
  props: {
    emailTemplateId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      isLoading: false,
      form: {
        emailName: '',
        locale: '',
        recipientRole: '',
        performerRole: '',
        subject: '',
        body: '',
        hasCustomContent: false,
        hasDefaultPath: false,
      },
      rules: {required: [required]},
      http: new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/admin/email-templates',
      ),
    };
  },
  beforeMount() {
    this.isLoading = true;
    this.http
      .get(this.emailTemplateId)
      .then(({data}) => {
        const item = data.data;
        this.form.emailName = item.email?.name || '';
        this.form.locale = item.locale || '';
        this.form.recipientRole = item.recipientRole || '';
        this.form.performerRole = item.performerRole || '';
        this.form.subject = item.subject || '';
        this.form.body = item.body || '';
        this.form.hasCustomContent = !!item.hasCustomContent;
        this.form.hasDefaultPath = !!(
          item.defaultSubjectPath || item.defaultBodyPath
        );
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      this.isLoading = true;
      this.http
        .update(this.emailTemplateId, {
          locale: this.form.locale,
          recipientRole: this.form.recipientRole,
          performerRole: this.form.performerRole || null,
          subject: this.form.subject,
          body: this.form.body,
        })
        .then(() => this.$toast.updateSuccess())
        .then(() => this.onCancel())
        .catch(() => {
          this.isLoading = false;
        });
    },
    onReset() {
      this.isLoading = true;
      this.http
        .update(this.emailTemplateId, {resetToDefault: true})
        .then(({data}) => {
          const item = data.data;
          this.form.subject = item.subject || '';
          this.form.body = item.body || '';
          this.form.hasCustomContent = !!item.hasCustomContent;
          return this.$toast.updateSuccess();
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onCancel() {
      navigate('/admin/viewEmailTemplates');
    },
  },
};
</script>
