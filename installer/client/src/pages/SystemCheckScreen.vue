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
  <oxd-form :loading="isLoading || preparing">
    <div class="orangehrm-installer-page">
      <oxd-text tag="h5" class="orangehrm-installer-page-title">
        System Check
      </oxd-text>
      <br />
      <oxd-text class="orangehrm-installer-page-content">
        In order for your OrangeHRM installation to function properly, please
        ensure that all of the system check items listed below are green. If any
        are red, please take the necessary steps to fix them.
      </oxd-text>
      <br />
      <oxd-text
        v-if="restoreMode"
        tag="p"
        class="orangehrm-installer-page-content"
      >
        Next will create the database and import your SQL dump before continuing.
      </oxd-text>
      <oxd-text
        v-if="errorMessage"
        tag="p"
        class="orangehrm-installer-page-content"
        style="color: #c62828"
      >
        {{ errorMessage }}
      </oxd-text>
      <br />
      <flex-table
        v-for="item in items"
        :key="item.category"
        :items="item.checks"
        :title-name="item.category"
      ></flex-table>
      <oxd-form-actions class="orangehrm-installer-page-action">
        <oxd-button display-type="ghost" label="Back" @click="navigateUrl" />
        <oxd-button
          class="orangehrm-left-space"
          display-type="ghost"
          label="Re-Check"
          type="submit"
          @click="reCheck"
        />
        <oxd-button
          class="orangehrm-left-space"
          display-type="secondary"
          :disabled="isInterrupted || preparing"
          label="Next"
          @click="goToScreen()"
        />
      </oxd-form-actions>
    </div>
  </oxd-form>
</template>
<script>
import {APIService} from '@/core/util/services/api.service';
import FlexTable from '@/components/FlexTable.vue';
import {navigate} from '@/core/util/helper/navigation.ts';
export default {
  name: 'SystemCheckScreen',
  components: {
    'flex-table': FlexTable,
  },
  props: {
    installer: {
      type: Boolean,
      default: false,
    },
    restoreMode: {
      type: Boolean,
      default: false,
    },
    skipAdminSeed: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `${props.installer ? 'installer' : 'upgrader'}/api/system-check`,
    );
    const modeHttp = new APIService(window.appGlobal.baseUrl, '');
    return {
      http,
      modeHttp,
    };
  },
  data() {
    return {
      items: [],
      isLoading: false,
      isInterrupted: true,
      preparing: false,
      errorMessage: '',
    };
  },
  beforeMount() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.isLoading = true;
      this.http
        .getAll()
        .then((response) => {
          const {data, meta} = response.data;
          this.items = data;
          this.isInterrupted = meta.isInterrupted;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    navigateUrl() {
      navigate(
        this.installer
          ? this.restoreMode
            ? '/installer/sql-dump-upload'
            : '/installer/database-config'
          : '/upgrader/database-config',
      );
    },
    async goToScreen() {
      if (!this.installer || !this.restoreMode) {
        navigate(
          this.installer
            ? '/installer/instance-creation'
            : '/upgrader/current-version',
        );
        return;
      }
      this.preparing = true;
      this.errorMessage = '';
      try {
        const {data} = await this.modeHttp.request({
          method: 'POST',
          url: '/installer/api/sql-dump/import',
          data: {},
          timeout: 0,
        });
        const skip = data?.data?.skipAdminSeed === true;
        navigate(skip ? '/installer/process' : '/installer/instance-creation');
      } catch (error) {
        this.errorMessage =
          error?.response?.data?.error?.message ??
          error?.message ??
          'Failed to import SQL dump';
        this.preparing = false;
      }
    },
    reCheck() {
      this.fetchData();
    },
  },
};
</script>
<style src="./installer-page.scss" lang="scss" scoped></style>
