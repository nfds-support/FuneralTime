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
  <oxd-form class="orangehrm-paper-container">
    <div class="orangehrm-timesheet-header">
      <div class="orangehrm-timesheet-header--title">
        <slot name="header-title"></slot>
      </div>
      <div class="orangehrm-timesheet-header--options">
        <slot name="header-options"></slot>
      </div>
    </div>

    <div v-if="loading" class="orangehrm-timesheet-loader">
      <oxd-loading-spinner />
    </div>
    <div
      v-else-if="!loading && !columns"
      class="orangehrm-timesheet-body-message"
    >
      <oxd-alert
        type="warn"
        :show="true"
        :message="$t('time.no_timesheets_found')"
      ></oxd-alert>
    </div>

    <div v-else class="orangehrm-timesheet-body">
      <table :class="tableClasses">
        <thead class="orangehrm-timesheet-table-header">
          <tr class="orangehrm-timesheet-table-header-row">
            <th class="orangehrm-timesheet-table-header-cell --freeze-left"></th>
            <th
              v-for="day in daysOfWeek"
              :key="day.id"
              class="orangehrm-timesheet-table-header-cell --center"
              :class="{'--stat-holiday': day.isHoliday}"
            >
              <span class="--day-name">{{ day.title }},</span>
              <span class="--day">{{ day.formattedDate }}</span>
              <span v-if="day.isHoliday" class="--holiday-label">
                {{ day.holidayName || $t('time.statutory_holiday') }}
              </span>
            </th>
          </tr>
        </thead>

        <tbody class="orangehrm-timesheet-table-body">
          <tr class="orangehrm-timesheet-table-body-row">
            <td class="orangehrm-timesheet-table-body-cell --freeze-left --label">
              {{ $t('time.start_time') }}
            </td>
            <td
              v-for="date in days"
              :key="`start-${date}`"
              class="orangehrm-timesheet-table-body-cell --center --duration-input"
            >
              <oxd-input-field
                v-if="editable"
                autocomplete="off"
                :placeholder="'HH:MM'"
                :model-value="getStartTime(date)"
                @update:model-value="updateStartTime($event, date)"
              />
              <span v-else>{{ getStartTime(date) || '—' }}</span>
            </td>
          </tr>

          <tr class="orangehrm-timesheet-table-body-row">
            <td class="orangehrm-timesheet-table-body-cell --freeze-left --label">
              {{ $t('time.end_time') }}
            </td>
            <td
              v-for="date in days"
              :key="`end-${date}`"
              class="orangehrm-timesheet-table-body-cell --center --duration-input"
            >
              <oxd-input-field
                v-if="editable"
                autocomplete="off"
                :placeholder="'HH:MM'"
                :model-value="getEndTime(date)"
                @update:model-value="updateEndTime($event, date)"
              />
              <span v-else>{{ getEndTime(date) || '—' }}</span>
            </td>
          </tr>

          <tr class="orangehrm-timesheet-table-body-row">
            <td class="orangehrm-timesheet-table-body-cell --freeze-left --label">
              {{ $t('time.break_deductions') }}
            </td>
            <td
              v-for="(day, index) in localDaysMeta"
              :key="`break-${day.date}`"
              class="orangehrm-timesheet-table-body-cell --center --duration-input"
            >
              <oxd-input-field
                v-if="editable"
                autocomplete="off"
                :placeholder="'HH:MM'"
                :model-value="day.breakDuration || '00:00'"
                @update:model-value="updateBreakDuration($event, index)"
              />
              <span v-else>{{ day.breakDuration || '00:00' }}</span>
            </td>
          </tr>

          <tr class="orangehrm-timesheet-table-body-row --total">
            <td class="orangehrm-timesheet-table-body-cell --freeze-left --label --highlight">
              {{ $t('time.total_hours') }}
            </td>
            <td
              v-for="date in days"
              :key="`total-${date}`"
              class="orangehrm-timesheet-table-body-cell --center --highlight"
            >
              {{ getTotalHours(date) }}
            </td>
          </tr>

          <tr
            v-if="onCallEnabled"
            class="orangehrm-timesheet-table-body-row --on-call"
          >
            <td class="orangehrm-timesheet-table-body-cell --freeze-left --label">
              {{ $t('time.on_call_question') }}
            </td>
            <td
              v-for="(day, index) in localDaysMeta"
              :key="`on-call-${day.date}`"
              class="orangehrm-timesheet-table-body-cell --center"
            >
              <oxd-input-field
                v-if="editable"
                v-model="localDaysMeta[index].onCall"
                type="checkbox"
                :true-value="true"
                :false-value="false"
                option-label=""
              />
              <oxd-text v-else tag="p">
                {{ day.onCall ? $t('general.yes') : $t('general.no') }}
              </oxd-text>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="orangehrm-timesheet-footer">
      <div class="orangehrm-timesheet-footer--title">
        <slot name="footer-title"></slot>
      </div>
      <div class="orangehrm-timesheet-footer--options">
        <slot name="footer-options"></slot>
      </div>
    </div>
  </oxd-form>
</template>

<script>
import {parseDate, parseTimeInSeconds} from '@ohrm/core/util/helper/datefns';
import {OxdAlert, OxdSpinner} from '@ohrm/oxd';

export default {
  name: 'Timesheet',

  components: {
    'oxd-alert': OxdAlert,
    'oxd-loading-spinner': OxdSpinner,
  },

  props: {
    records: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Object,
      required: false,
      default: () => null,
    },
    subtotal: {
      type: String,
      required: false,
      default: null,
    },
    editable: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    timesheetId: {
      type: Number,
      default: null,
    },
    daysMeta: {
      type: Array,
      default: () => [],
    },
    onCallEnabled: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['update:records', 'update:daysMeta'],

  data() {
    return {
      localDaysMeta: [],
    };
  },

  watch: {
    daysMeta: {
      immediate: true,
      deep: true,
      handler(value) {
        const dates = this.columns ? Object.keys(this.columns) : [];
        if (value && value.length) {
          this.localDaysMeta = JSON.parse(JSON.stringify(value)).map((day) => ({
            date: day.date,
            onCall: !!day.onCall,
            breakDuration: day.breakDuration || '00:00',
            isHoliday: !!day.isHoliday,
            holidayName: day.holidayName || null,
          }));
        } else if (dates.length) {
          this.localDaysMeta = dates.map((date) => ({
            date,
            onCall: false,
            breakDuration: '00:00',
            isHoliday: !!this.columns?.[date]?.isHoliday,
            holidayName: null,
          }));
        } else {
          this.localDaysMeta = [];
        }
      },
    },
    localDaysMeta: {
      deep: true,
      handler(value) {
        if (!this.editable) return;
        this.$emit('update:daysMeta', value);
      },
    },
    columns: {
      immediate: true,
      handler() {
        this.ensureSingleRecord();
      },
    },
  },

  computed: {
    days() {
      return this.columns ? Object.keys(this.columns) : [];
    },
    clockRecord() {
      return this.records?.[0] || {dates: {}};
    },
    daysOfWeek() {
      const days = [
        this.$t('general.sun'),
        this.$t('general.mon'),
        this.$t('general.tue'),
        this.$t('general.wed'),
        this.$t('general.thu'),
        this.$t('general.fri'),
        this.$t('general.sat'),
      ];
      return this.days.map((day) => {
        const date = parseDate(day, 'yyyy-MM-dd');
        const meta = this.localDaysMeta.find((item) => item.date === day) || {};
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const dayNum = String(date.getDate()).padStart(2, '0');
        return {
          id: date.valueOf(),
          day: date.getDate(),
          dayLabel: day,
          formattedDate: `${month}/${dayNum}`,
          title: days[date.getDay()],
          isHoliday: !!meta.isHoliday || !!this.columns?.[day]?.isHoliday,
          holidayName: meta.holidayName || null,
        };
      });
    },
    tableClasses() {
      return {
        'orangehrm-timesheet-table': true,
        '--editable': this.editable,
        '--clock-layout': true,
      };
    },
  },

  methods: {
    ensureSingleRecord() {
      if (!this.editable || !this.columns) return;
      if (this.records.length === 0) {
        this.$emit('update:records', [{project: null, activity: null, dates: {}}]);
      }
    },
    getEntry(date) {
      return this.clockRecord.dates?.[date] || null;
    },
    getStartTime(date) {
      return this.getEntry(date)?.startTime ?? '';
    },
    getEndTime(date) {
      return this.getEntry(date)?.endTime ?? '';
    },
    updateStartTime($value, date) {
      this.updateClockField('startTime', $value, date);
    },
    updateEndTime($value, date) {
      this.updateClockField('endTime', $value, date);
    },
    updateBreakDuration($value, index) {
      const updated = [...this.localDaysMeta];
      updated[index] = {
        ...updated[index],
        breakDuration: $value || '00:00',
      };
      this.localDaysMeta = updated;
      this.recalculateDuration(updated[index].date);
    },
    updateClockField(field, $value, date) {
      const current = {
        date,
        duration: this.getEntry(date)?.duration,
        startTime: this.getEntry(date)?.startTime,
        endTime: this.getEntry(date)?.endTime,
        id: this.getEntry(date)?.id,
        comment: this.getEntry(date)?.comment,
        [field]: $value,
      };
      this.applyDuration(current);
      const dates = {...(this.clockRecord.dates || {}), [date]: current};
      this.syncRecords([{...(this.clockRecord || {}), dates}]);
    },
    recalculateDuration(date) {
      const current = {
        date,
        duration: this.getEntry(date)?.duration,
        startTime: this.getEntry(date)?.startTime,
        endTime: this.getEntry(date)?.endTime,
        id: this.getEntry(date)?.id,
        comment: this.getEntry(date)?.comment,
      };
      this.applyDuration(current);
      const dates = {...(this.clockRecord.dates || {}), [date]: current};
      this.syncRecords([{...(this.clockRecord || {}), dates}]);
    },
    applyDuration(entry) {
      if (entry.startTime && entry.endTime) {
        const start = parseTimeInSeconds(entry.startTime);
        const end = parseTimeInSeconds(entry.endTime);
        if (start >= 0 && end >= 0) {
          let seconds = end - start;
          if (seconds < 0) seconds += 24 * 3600;
          const breakMeta = this.localDaysMeta.find((d) => d.date === entry.date);
          const breakSeconds = parseTimeInSeconds(breakMeta?.breakDuration || '00:00');
          if (breakSeconds > 0) {
            seconds = Math.max(0, seconds - breakSeconds);
          }
          const hours = Math.floor(seconds / 3600)
            .toString()
            .padStart(2, '0');
          const minutes = Math.floor((seconds % 3600) / 60)
            .toString()
            .padStart(2, '0');
          entry.duration = `${hours}:${minutes}`;
        }
      }
    },
    getTotalHours(date) {
      const entry = this.getEntry(date);
      if (entry?.duration) return entry.duration;
      if (this.columns?.[date]?.net?.label) return this.columns[date].net.label;
      if (this.columns?.[date]?.total?.label) return this.columns[date].total.label;
      return '00:00';
    },
    syncRecords(updated) {
      if (!this.editable) return;
      this.$emit('update:records', updated);
    },
  },
};
</script>

<style src="./timesheet.scss" lang="scss" scoped></style>
