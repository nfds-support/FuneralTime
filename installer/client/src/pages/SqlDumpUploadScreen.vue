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
  <oxd-form
    class="orangehrm-installer-page"
    :loading="isLoading"
    @submit-valid="onSubmit"
  >
    <oxd-text tag="h5" class="orangehrm-installer-page-title">
      Restore from SQL Dump
    </oxd-text>
    <br />
    <oxd-text tag="p" class="orangehrm-installer-page-content">
      Upload a full OrangeHRM / FuneralTime database dump (
      <b>.sql</b>
      ). FuneralTime will create the database you configured, import this file,
      then upgrade the schema to the current product version.
    </oxd-text>
    <br />
    <oxd-text tag="p" class="orangehrm-installer-page-content">
      Large dumps are better imported via CLI:
      <code>php installer/console install:from-dump --sql=/path/to/dump.sql</code>
    </oxd-text>
    <br />
    <oxd-text tag="p" class="orangehrm-installer-page-content">
      If the dump used data encryption, copy
      <code>lib/confs/cryptokeys/key.ohrm</code>
      from the old installation after install completes.
    </oxd-text>
    <br />
    <oxd-form-row>
      <input
        ref="fileInput"
        type="file"
        accept=".sql,application/sql,text/plain"
        @change="onFileChange"
      />
    </oxd-form-row>
    <oxd-text
      v-if="selectedName"
      tag="p"
      class="orangehrm-installer-page-content"
    >
      Selected: <b>{{ selectedName }}</b>
      <span v-if="uploadedName"> (uploaded)</span>
    </oxd-text>
    <oxd-text
      v-if="errorMessage"
      tag="p"
      class="orangehrm-installer-page-content orangehrm-sql-dump-error"
    >
      {{ errorMessage }}
    </oxd-text>

    <oxd-form-actions class="orangehrm-installer-page-action">
      <oxd-button display-type="ghost" label="Back" @click="goBack" />
      <oxd-button
        class="orangehrm-left-space"
        display-type="secondary"
        label="Next"
        type="submit"
        :disabled="!selectedFile && !uploadedName"
      />
    </oxd-form-actions>
  </oxd-form>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {navigate} from '@/core/util/helper/navigation.ts';

export default {
  name: 'SqlDumpUploadScreen',
  data() {
    return {
      isLoading: false,
      selectedFile: null,
      selectedName: '',
      uploadedName: '',
      errorMessage: '',
      http: new APIService(window.appGlobal.baseUrl, ''),
    };
  },
  beforeMount() {
    this.http
      .request({
        method: 'GET',
        url: '/installer/api/sql-dump/upload',
      })
      .then(({data}) => {
        if (data?.data?.filename) {
          this.uploadedName = data.data.filename;
          this.selectedName = data.data.filename;
        }
      })
      .catch(() => {
        /* ignore */
      });
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files?.[0] ?? null;
      this.selectedFile = file;
      this.selectedName = file ? file.name : '';
      this.uploadedName = '';
      this.errorMessage = '';
    },
    goBack() {
      navigate('/installer/database-config');
    },
    async onSubmit() {
      this.errorMessage = '';
      if (!this.selectedFile && this.uploadedName) {
        navigate('/installer/system-check');
        return;
      }
      if (!this.selectedFile) {
        this.errorMessage = 'Please choose a .sql dump file.';
        return;
      }
      if (!/\.sql$/i.test(this.selectedFile.name)) {
        this.errorMessage = 'Only .sql files are allowed.';
        return;
      }
      this.isLoading = true;
      const formData = new FormData();
      formData.append('sqlDump', this.selectedFile);
      try {
        const {data} = await this.http.request({
          method: 'POST',
          url: '/installer/api/sql-dump/upload',
          data: formData,
          headers: {'Content-Type': 'multipart/form-data'},
          transformRequest: [(d) => d],
        });
        this.uploadedName = data?.data?.filename ?? this.selectedFile.name;
        navigate('/installer/system-check');
      } catch (error) {
        this.errorMessage =
          error?.response?.data?.error?.message ??
          error?.message ??
          'Upload failed';
        this.isLoading = false;
      }
    },
  },
};
</script>

<style src="./installer-page.scss" lang="scss" scoped></style>
<style lang="scss" scoped>
.orangehrm-sql-dump-error {
  color: $oxd-feedback-danger-color;
}
code {
  font-size: 0.9em;
}
</style>
