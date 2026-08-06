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
        {{ $t('policy.learning') }}
      </oxd-text>
      <oxd-divider />
      <oxd-text tag="p">
        {{ $t('policy.learning_help') }}
      </oxd-text>
      <oxd-form-actions>
        <oxd-button
          v-if="moodleBaseUrl"
          :label="$t('policy.open_moodle')"
          display-type="secondary"
          @click="openMoodle"
        />
        <oxd-button
          :label="$t('policy.my_policies')"
          display-type="ghost"
          @click="onPolicies"
        />
      </oxd-form-actions>
      <oxd-text v-if="!moodleBaseUrl" tag="p">
        {{ $t('policy.moodle_not_configured') }}
      </oxd-text>
    </div>
  </div>
</template>

<script>
import {navigate} from '@/core/util/helper/navigation';

export default {
  props: {
    moodleBaseUrl: {type: String, default: ''},
  },
  methods: {
    openMoodle() {
      if (this.moodleBaseUrl) {
        window.open(this.moodleBaseUrl, '_blank', 'noopener');
      }
    },
    onPolicies() {
      navigate('/policy/viewMyPolicies');
    },
  },
};
</script>
