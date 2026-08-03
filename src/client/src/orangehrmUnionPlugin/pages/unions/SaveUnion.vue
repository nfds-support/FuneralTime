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
        {{ unionId ? $t('union.edit_union') : $t('union.add_union') }}
      </oxd-text>
      <oxd-divider />
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.name"
                required
                :label="$t('general.name')"
                :rules="[required]"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.active"
                type="switch"
                :label="$t('general.status')"
              />
            </oxd-grid-item>
            <oxd-grid-item class=" --span-column-2">
              <oxd-input-field
                v-model="form.description"
                type="textarea"
                :label="$t('general.description')"
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
            @click="navigate('/union/viewUnions')"
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

export default {
  props: {
    unionId: {type: Number, default: null},
  },
  setup() {
    return {
      navigate,
      required,
      http: new APIService(window.appGlobal.baseUrl, '/api/v2/union/unions'),
    };
  },
  data() {
    return {
      isLoading: false,
      form: {name: '', description: '', active: true},
    };
  },
  beforeMount() {
    if (!this.unionId) return;
    this.isLoading = true;
    this.http
      .get(this.unionId)
      .then(({data}) => {
        this.form.name = data.data.name;
        this.form.description = data.data.description;
        this.form.active = !!data.data.active;
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      this.isLoading = true;
      const payload = {...this.form};
      const req = this.unionId
        ? this.http.update(this.unionId, payload)
        : this.http.create(payload);
      req
        .then(() => this.$toast.saveSuccess())
        .then(() => navigate('/union/viewUnions'))
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
