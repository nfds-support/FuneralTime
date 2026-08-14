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
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('time.timesheet_reminders') }}
        </oxd-text>
        <oxd-switch-input
          v-model="form.enabled"
          label-position="left"
          :option-label="$t('general.enable')"
        />
      </div>
      <oxd-divider />
      <oxd-text class="orangehrm-input-hint" tag="p">
        {{ $t('time.timesheet_reminders_hint') }}
      </oxd-text>
      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.weekday"
                type="select"
                :options="weekdayOptions"
                :show-empty-selector="false"
                :rules="rules.required"
                :label="$t('time.reminder_weekday')"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.sendTime"
                type="time"
                :step="1"
                :rules="rules.required"
                :label="$t('time.reminder_send_time')"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.timezone"
                type="select"
                :options="timezoneOptions"
                :show-empty-selector="false"
                :rules="rules.required"
                :label="$t('attendance.timezone')"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.jobTitles"
                type="multiselect"
                :label="$t('time.reminder_job_titles')"
                :options="jobTitleOptions"
                :rules="rules.recipients"
              />
              <oxd-text class="orangehrm-input-hint" tag="p">
                {{ $t('time.help_reminder_job_titles') }}
              </oxd-text>
            </oxd-grid-item>
            <oxd-grid-item>
              <employee-autocomplete
                v-model="form.employees"
                :multiple="true"
                :clear="false"
                :params="{includeEmployees: 'onlyCurrent'}"
                :label="$t('time.reminder_employees')"
                :rules="rules.recipients"
              />
              <oxd-text class="orangehrm-input-hint" tag="p">
                {{ $t('time.help_reminder_employees') }}
              </oxd-text>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <required-text />
          <oxd-button
            type="submit"
            display-type="secondary"
            :label="$t('general.save')"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {onBeforeMount, reactive, ref} from 'vue';
import {OxdSwitchInput} from '@ohrm/oxd';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@/core/util/validation/rules';
import useToast from '@/core/util/composable/useToast';
import usei18n from '@/core/util/composable/usei18n';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';

const apiPath = 'api/v2/time/config/timesheet-reminders';

export default {
  name: 'TimesheetReminderConfig',
  components: {
    'oxd-switch-input': OxdSwitchInput,
    'employee-autocomplete': EmployeeAutocomplete,
  },
  setup() {
    const isLoading = ref(false);
    const {success} = useToast();
    const {$t} = usei18n();
    const http = new APIService(window.appGlobal.baseUrl, apiPath);
    const jobTitleHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/job-titles',
    );
    const timezonesHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/timezones',
    );
    const form = reactive({
      enabled: false,
      weekday: null,
      sendTime: '16:00',
      timezone: null,
      jobTitles: [],
      employees: [],
    });
    const jobTitleOptions = ref([]);
    const timezoneOptions = ref([]);
    const weekdayOptions = [
      {id: 0, label: $t('general.sunday')},
      {id: 1, label: $t('general.monday')},
      {id: 2, label: $t('general.tuesday')},
      {id: 3, label: $t('general.wednesday')},
      {id: 4, label: $t('general.thursday')},
      {id: 5, label: $t('general.friday')},
      {id: 6, label: $t('general.saturday')},
    ];
    const recipientsRequired = () => {
      if (!form.enabled) {
        return true;
      }
      const hasJobTitles = Array.isArray(form.jobTitles) && form.jobTitles.length > 0;
      const hasEmployees = Array.isArray(form.employees) && form.employees.length > 0;
      return hasJobTitles || hasEmployees || $t('time.reminder_recipients_required');
    };
    const rules = {
      required: [required],
      recipients: [recipientsRequired],
    };

    const employeeLabel = (employee) =>
      `${employee.firstName} ${employee.middleName || ''} ${employee.lastName}`
        .replace(/\s+/g, ' ')
        .trim();

    const load = async () => {
      isLoading.value = true;
      try {
        const [{data: tzData}, {data: jtData}, {data}] = await Promise.all([
          timezonesHttp.getAll(),
          jobTitleHttp.getAll({limit: 0}),
          http.getAll(),
        ]);
        timezoneOptions.value = (tzData?.data || []).map((timezone) => ({
          id: timezone.name,
          label: `(GMT${timezone.label}) ${timezone.name}`,
        }));
        jobTitleOptions.value = (jtData?.data || []).map((item) => ({
          id: item.id,
          label: item.title,
        }));
        const payload = data?.data || {};
        form.enabled = !!payload.enabled;
        form.weekday =
          weekdayOptions.find((day) => day.id === Number(payload.weekday)) ||
          weekdayOptions[5];
        form.sendTime = payload.sendTime || '16:00';
        form.timezone =
          timezoneOptions.value.find((tz) => tz.id === payload.timezone) || {
            id: payload.timezone || 'UTC',
            label: payload.timezone || 'UTC',
          };
        form.jobTitles = (payload.jobTitles || []).map((jobTitle) => ({
          id: jobTitle.id,
          label: jobTitle.title,
        }));
        form.employees = (payload.employees || []).map((employee) => ({
          id: employee.empNumber,
          label: employeeLabel(employee),
        }));
      } finally {
        isLoading.value = false;
      }
    };

    const onSave = async () => {
      isLoading.value = true;
      try {
        const sendTime = String(form.sendTime || '').substring(0, 5);
        await http.request({
          method: 'PUT',
          data: {
            enabled: !!form.enabled,
            weekday: form.weekday?.id ?? 5,
            sendTime,
            timezone: form.timezone?.id || 'UTC',
            jobTitleIds: (form.jobTitles || []).map((jobTitle) => jobTitle.id),
            empNumbers: (form.employees || []).map((employee) => employee.id),
          },
        });
        success({toastMessage: 'Successfully Updated'});
      } finally {
        isLoading.value = false;
      }
    };

    onBeforeMount(load);

    return {
      isLoading,
      form,
      rules,
      weekdayOptions,
      timezoneOptions,
      jobTitleOptions,
      onSave,
    };
  },
};
</script>
