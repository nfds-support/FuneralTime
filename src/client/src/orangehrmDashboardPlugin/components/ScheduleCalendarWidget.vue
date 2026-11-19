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
  <oxd-sheet :gutters="false" class="schedule-calendar">
    <div class="schedule-calendar-header">
      <div class="schedule-calendar-title">
        <oxd-icon name="calendar" class="schedule-calendar-icon" />
        <oxd-text tag="p">Employee Schedule</oxd-text>
      </div>
      <div class="schedule-calendar-nav">
        <oxd-icon-button
          name="chevron-left"
          :disabled="isLoading"
          @click="shiftWeek(-1)"
        />
        <oxd-text tag="p" class="schedule-calendar-period">
          Period: {{ periodLabel }}
        </oxd-text>
        <oxd-icon-button
          name="chevron-right"
          :disabled="isLoading"
          @click="shiftWeek(1)"
        />
        <oxd-button
          v-if="canEdit"
          display-type="secondary"
          label="Add employee"
          :disabled="isLoading"
          @click="openAddEmployeeDialog"
        />
        <oxd-button
          v-if="canEdit"
          display-type="secondary"
          label="Copy previous week"
          :disabled="isLoading"
          @click="copyPreviousWeek"
        />
      </div>
    </div>
    <oxd-divider />

    <oxd-loading-spinner v-if="isLoading" class="schedule-calendar-loader" />

    <div v-else class="schedule-calendar-body">
      <oxd-text
        v-if="employees.length === 0"
        tag="p"
        class="schedule-calendar-empty"
      >
        No employees are on the schedule for this week. Use Add employee to
        include someone.
      </oxd-text>
      <div class="schedule-calendar-scroll">
        <table class="schedule-grid">
          <thead>
            <tr>
              <th class="schedule-grid-name">Employee</th>
              <th v-for="day in days" :key="day.iso">
                <div>{{ day.weekday }}</div>
                <div class="schedule-grid-day-date">{{ day.label }}</div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in employees" :key="emp.empNumber">
              <td class="schedule-grid-name">
                {{ emp.firstName }} {{ emp.lastName }}
              </td>
              <td
                v-for="day in days"
                :key="`${emp.empNumber}-${day.iso}`"
                class="schedule-grid-cell"
                :class="{'schedule-grid-cell--editable': canEdit}"
                :style="cellStyle(emp.empNumber, day.iso)"
                @click="onCellClick(emp, day)"
              >
                <span class="schedule-grid-cell-label">
                  {{ cellLabel(emp.empNumber, day.iso) }}
                </span>
                <span
                  v-if="leaveLabel(emp.empNumber, day.iso)"
                  class="schedule-grid-leave"
                >
                  {{ leaveLabel(emp.empNumber, day.iso) }}
                </span>
              </td>
            </tr>
            <tr class="schedule-grid-oncall">
              <td class="schedule-grid-name">On-Call</td>
              <td
                v-for="day in days"
                :key="`oncall-${day.iso}`"
                class="schedule-grid-cell"
                :class="{'schedule-grid-cell--editable': canEdit}"
                @click="onOnCallClick(day)"
              >
                {{ onCallLabel(day.iso) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="schedule-legend">
        <oxd-text tag="p" class="schedule-legend-title">Colour</oxd-text>
        <div
          v-for="type in shiftTypes"
          :key="type.id"
          class="schedule-legend-item"
        >
          <span
            class="schedule-legend-swatch"
            :style="{backgroundColor: type.color}"
          ></span>
          <span>{{ type.name }}</span>
        </div>
      </div>
    </div>

    <oxd-dialog
      v-if="cellDialog.show"
      class="orangehrm-dialog-modal"
      @update:show="closeCellDialog"
    >
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">
          {{ cellDialog.employeeName }} — {{ cellDialog.dayLabel }}
        </oxd-text>
      </div>
      <oxd-divider />
      <oxd-form @submit-valid="saveCell">
        <oxd-form-row>
          <oxd-input-field
            v-model="cellDialog.shiftType"
            type="select"
            label="Status"
            :options="shiftTypeOptions"
            :clear="true"
          />
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="button"
            display-type="ghost"
            label="Cancel"
            @click="closeCellDialog"
          />
          <oxd-button type="submit" display-type="secondary" label="Save" />
        </oxd-form-actions>
      </oxd-form>
    </oxd-dialog>

    <oxd-dialog
      v-if="onCallDialog.show"
      class="orangehrm-dialog-modal"
      @update:show="closeOnCallDialog"
    >
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">On-Call — {{ onCallDialog.dayLabel }}</oxd-text>
      </div>
      <oxd-divider />
      <oxd-form @submit-valid="saveOnCall">
        <oxd-form-row>
          <oxd-input-field
            v-model="onCallDialog.employee"
            type="select"
            label="Employee"
            :options="employeeOptions"
            :clear="true"
          />
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="button"
            display-type="ghost"
            label="Cancel"
            @click="closeOnCallDialog"
          />
          <oxd-button type="submit" display-type="secondary" label="Save" />
        </oxd-form-actions>
      </oxd-form>
    </oxd-dialog>

    <oxd-dialog
      v-if="addEmployeeDialog.show"
      class="orangehrm-dialog-modal"
      @update:show="closeAddEmployeeDialog"
    >
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">Add employee to schedule</oxd-text>
      </div>
      <oxd-divider />
      <oxd-form @submit-valid="saveAddEmployee">
        <oxd-form-row>
          <oxd-input-field
            v-model="addEmployeeDialog.employee"
            type="select"
            label="Employee"
            :options="addableEmployeeOptions"
            :rules="[required]"
          />
        </oxd-form-row>
        <oxd-form-row>
          <oxd-input-field
            v-model="addEmployeeDialog.day"
            type="select"
            label="Day (optional)"
            :options="dayOptions"
            :clear="true"
          />
        </oxd-form-row>
        <oxd-form-row>
          <oxd-input-field
            v-model="addEmployeeDialog.shiftType"
            type="select"
            label="Status (optional)"
            :options="shiftTypeOptions"
            :clear="true"
          />
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="button"
            display-type="ghost"
            label="Cancel"
            @click="closeAddEmployeeDialog"
          />
          <oxd-button type="submit" display-type="secondary" label="Add" />
        </oxd-form-actions>
      </oxd-form>
    </oxd-dialog>
  </oxd-sheet>
</template>

<script>
import {
  OxdButton,
  OxdDialog,
  OxdDivider,
  OxdForm,
  OxdFormActions,
  OxdFormRow,
  OxdIcon,
  OxdIconButton,
  OxdInputField,
  OxdSheet,
  OxdSpinner,
  OxdText,
} from '@ohrm/oxd';
import {APIService} from '@/core/util/services/api.service';
import {formatDate, parseDate} from '@ohrm/core/util/helper/datefns';
import useToast from '@/core/util/composable/useToast';
import {required} from '@/core/util/validation/rules';

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/**
 * Employees visible on the grid: those with a shift, leave, or on-call
 * assignment in the week, plus any locally pinned (Add employee) rows.
 */
export function filterScheduledEmployees(
  allEmployees,
  assignmentMap,
  leaveMap,
  onCallMap,
  pinnedEmpNumbers,
) {
  const scheduled = new Set(pinnedEmpNumbers || []);
  for (const key of Object.keys(assignmentMap || {})) {
    const empNumber = Number(key.split('|')[0]);
    if (!Number.isNaN(empNumber)) {
      scheduled.add(empNumber);
    }
  }
  for (const key of Object.keys(leaveMap || {})) {
    const empNumber = Number(key.split('|')[0]);
    if (!Number.isNaN(empNumber)) {
      scheduled.add(empNumber);
    }
  }
  for (const entry of Object.values(onCallMap || {})) {
    if (entry?.empNumber != null) {
      scheduled.add(Number(entry.empNumber));
    }
  }
  return (allEmployees || []).filter((emp) => scheduled.has(emp.empNumber));
}

export default {
  name: 'ScheduleCalendarWidget',
  components: {
    'oxd-sheet': OxdSheet,
    'oxd-icon': OxdIcon,
    'oxd-text': OxdText,
    'oxd-button': OxdButton,
    'oxd-icon-button': OxdIconButton,
    'oxd-divider': OxdDivider,
    'oxd-loading-spinner': OxdSpinner,
    'oxd-dialog': OxdDialog,
    'oxd-form': OxdForm,
    'oxd-form-row': OxdFormRow,
    'oxd-form-actions': OxdFormActions,
    'oxd-input-field': OxdInputField,
  },
  setup() {
    const {success} = useToast();
    return {toastSuccess: success, required};
  },
  data() {
    return {
      isLoading: false,
      fromDate: null,
      toDate: null,
      allEmployees: [],
      pinnedEmpNumbers: [],
      shiftTypes: [],
      assignmentMap: {},
      leaveMap: {},
      onCallMap: {},
      weekHttp: null,
      typesHttp: null,
      assignmentHttp: null,
      onCallHttp: null,
      copyHttp: null,
      cellDialog: {
        show: false,
        empNumber: null,
        employeeName: '',
        date: null,
        dayLabel: '',
        shiftType: null,
      },
      onCallDialog: {
        show: false,
        date: null,
        dayLabel: '',
        employee: null,
      },
      addEmployeeDialog: {
        show: false,
        employee: null,
        day: null,
        shiftType: null,
      },
    };
  },
  computed: {
    canEdit() {
      return this.$can.update('time_schedule');
    },
    shiftTypeById() {
      const map = {};
      for (const t of this.shiftTypes) {
        map[t.id] = t;
      }
      return map;
    },
    shiftTypeOptions() {
      return this.shiftTypes.map((t) => ({id: t.id, label: t.name}));
    },
    employeeOptions() {
      return this.allEmployees.map((e) => ({
        id: e.empNumber,
        label: `${e.firstName} ${e.lastName}`,
      }));
    },
    employees() {
      return filterScheduledEmployees(
        this.allEmployees,
        this.assignmentMap,
        this.leaveMap,
        this.onCallMap,
        this.pinnedEmpNumbers,
      );
    },
    addableEmployeeOptions() {
      const visible = new Set(this.employees.map((e) => e.empNumber));
      return this.employeeOptions.filter((o) => !visible.has(o.id));
    },
    dayOptions() {
      return this.days.map((d) => ({
        id: d.iso,
        label: `${d.weekday} ${d.label}`,
      }));
    },
    days() {
      if (!this.fromDate) return [];
      const start = parseDate(this.fromDate);
      if (!start) return [];
      const result = [];
      for (let i = 0; i < 7; i++) {
        const d = new Date(start.getTime());
        d.setDate(d.getDate() + i);
        result.push({
          iso: formatDate(d, 'yyyy-MM-dd'),
          weekday: WEEKDAYS[i],
          label: formatDate(d, 'MMM d'),
        });
      }
      return result;
    },
    periodLabel() {
      if (!this.fromDate || !this.toDate) return '';
      const from = parseDate(this.fromDate);
      const to = parseDate(this.toDate);
      if (!from || !to) return '';
      return `${formatDate(from, 'MMMM d')} to ${formatDate(to, 'MMMM d, yyyy')}`;
    },
  },
  created() {
    this.weekHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/schedule/week',
    );
    this.typesHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/schedule/shift-types',
    );
    this.assignmentHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/schedule/assignments',
    );
    this.onCallHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/schedule/on-call',
    );
    this.copyHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/time/schedule/copy-week',
    );
  },
  beforeMount() {
    this.load();
  },
  methods: {
    applyWeek(week, {resetPins = false} = {}) {
      this.fromDate = week.fromDate;
      this.toDate = week.toDate;
      this.allEmployees = week.employees || [];
      if (resetPins) {
        this.pinnedEmpNumbers = [];
      }
      const aMap = {};
      for (const a of week.assignments || []) {
        aMap[`${a.empNumber}|${a.date}`] = a;
      }
      this.assignmentMap = aMap;
      const lMap = {};
      for (const l of week.leaves || []) {
        lMap[`${l.empNumber}|${l.date}`] = l.leaveType;
      }
      this.leaveMap = lMap;
      const oMap = {};
      for (const o of week.onCall || []) {
        oMap[o.date] = o;
      }
      this.onCallMap = oMap;
      // Keep pins only for employees who still have no week activity.
      const withActivity = new Set(
        filterScheduledEmployees(
          this.allEmployees,
          this.assignmentMap,
          this.leaveMap,
          this.onCallMap,
          [],
        ).map((e) => e.empNumber),
      );
      this.pinnedEmpNumbers = this.pinnedEmpNumbers.filter(
        (empNumber) => !withActivity.has(empNumber),
      );
    },
    async load() {
      this.isLoading = true;
      try {
        const [typesRes, weekRes] = await Promise.all([
          this.typesHttp.getAll(),
          this.weekHttp.getAll(
            this.fromDate ? {fromDate: this.fromDate} : undefined,
          ),
        ]);
        this.shiftTypes = typesRes?.data?.data || [];
        const week = (weekRes?.data?.data || [])[0];
        if (week) {
          this.applyWeek(week);
        }
      } finally {
        this.isLoading = false;
      }
    },
    async shiftWeek(delta) {
      if (!this.fromDate) return;
      const start = parseDate(this.fromDate);
      if (!start) return;
      start.setDate(start.getDate() + delta * 7);
      this.fromDate = formatDate(start, 'yyyy-MM-dd');
      this.pinnedEmpNumbers = [];
      await this.load();
    },
    cellKey(empNumber, iso) {
      return `${empNumber}|${iso}`;
    },
    cellStyle(empNumber, iso) {
      const a = this.assignmentMap[this.cellKey(empNumber, iso)];
      if (!a) return {};
      const type = this.shiftTypeById[a.shiftTypeId];
      return type ? {backgroundColor: type.color} : {};
    },
    cellLabel(empNumber, iso) {
      const a = this.assignmentMap[this.cellKey(empNumber, iso)];
      if (!a) return '';
      const type = this.shiftTypeById[a.shiftTypeId];
      return type ? type.name : '';
    },
    leaveLabel(empNumber, iso) {
      return this.leaveMap[this.cellKey(empNumber, iso)] || '';
    },
    onCallLabel(iso) {
      const o = this.onCallMap[iso];
      return o ? `${o.firstName} ${o.lastName}` : '';
    },
    onCellClick(emp, day) {
      if (!this.canEdit) return;
      const a = this.assignmentMap[this.cellKey(emp.empNumber, day.iso)];
      this.cellDialog = {
        show: true,
        empNumber: emp.empNumber,
        employeeName: `${emp.firstName} ${emp.lastName}`,
        date: day.iso,
        dayLabel: `${day.weekday} ${day.label}`,
        shiftType: a
          ? this.shiftTypeOptions.find((o) => o.id === a.shiftTypeId) || null
          : null,
      };
    },
    closeCellDialog() {
      this.cellDialog.show = false;
    },
    async saveCell() {
      await this.assignmentHttp.request({
        method: 'PUT',
        data: {
          empNumber: this.cellDialog.empNumber,
          date: this.cellDialog.date,
          shiftTypeId: this.cellDialog.shiftType?.id ?? null,
        },
      });
      this.closeCellDialog();
      this.toastSuccess({toastMessage: 'Schedule updated'});
      await this.load();
    },
    onOnCallClick(day) {
      if (!this.canEdit) return;
      const o = this.onCallMap[day.iso];
      this.onCallDialog = {
        show: true,
        date: day.iso,
        dayLabel: `${day.weekday} ${day.label}`,
        employee: o
          ? this.employeeOptions.find((e) => e.id === o.empNumber) || null
          : null,
      };
    },
    closeOnCallDialog() {
      this.onCallDialog.show = false;
    },
    async saveOnCall() {
      await this.onCallHttp.request({
        method: 'PUT',
        data: {
          date: this.onCallDialog.date,
          empNumber: this.onCallDialog.employee?.id ?? null,
        },
      });
      this.closeOnCallDialog();
      this.toastSuccess({toastMessage: 'On-call updated'});
      await this.load();
    },
    openAddEmployeeDialog() {
      if (!this.canEdit) return;
      this.addEmployeeDialog = {
        show: true,
        employee: null,
        day: null,
        shiftType: null,
      };
    },
    closeAddEmployeeDialog() {
      this.addEmployeeDialog.show = false;
    },
    async saveAddEmployee() {
      const empNumber = this.addEmployeeDialog.employee?.id;
      if (empNumber == null) return;
      const dayIso = this.addEmployeeDialog.day?.id ?? null;
      const shiftTypeId = this.addEmployeeDialog.shiftType?.id ?? null;
      if (dayIso && shiftTypeId != null) {
        await this.assignmentHttp.request({
          method: 'PUT',
          data: {empNumber, date: dayIso, shiftTypeId},
        });
        this.closeAddEmployeeDialog();
        this.toastSuccess({toastMessage: 'Employee added to schedule'});
        await this.load();
        return;
      }
      if (!this.pinnedEmpNumbers.includes(empNumber)) {
        this.pinnedEmpNumbers = [...this.pinnedEmpNumbers, empNumber];
      }
      this.closeAddEmployeeDialog();
      this.toastSuccess({toastMessage: 'Employee added to schedule'});
    },
    async copyPreviousWeek() {
      this.isLoading = true;
      try {
        const res = await this.copyHttp.create({fromDate: this.fromDate});
        const week = (res?.data?.data || [])[0];
        if (week) {
          this.applyWeek(week, {resetPins: true});
        }
        this.toastSuccess({toastMessage: 'Previous week copied'});
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.schedule-calendar {
  margin: 0 auto 1.5rem;
  max-width: 1100px;
  padding: 1rem 1.25rem 1.25rem;
}

.schedule-calendar-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.schedule-calendar-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
}

.schedule-calendar-icon {
  font-size: 1.25rem;
}

.schedule-calendar-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.schedule-calendar-period {
  min-width: 220px;
  text-align: center;
  font-weight: 600;
}

.schedule-calendar-loader {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.schedule-calendar-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.schedule-calendar-empty {
  color: #666;
  font-size: 0.9rem;
}

.schedule-calendar-scroll {
  overflow-x: auto;
}

.schedule-grid {
  width: 100%;
  border-collapse: collapse;
  min-width: 720px;
  font-size: 0.85rem;

  th,
  td {
    border: 1px solid #d3d3d3;
    padding: 0.4rem 0.35rem;
    text-align: center;
    vertical-align: middle;
  }

  th {
    background: #f0f0f0;
    font-weight: 600;
  }
}

.schedule-grid-name {
  text-align: left !important;
  white-space: nowrap;
  min-width: 120px;
  font-weight: 600;
  background: #fafafa;
}

.schedule-grid-day-date {
  font-weight: 400;
  font-size: 0.75rem;
  color: #666;
}

.schedule-grid-cell {
  min-width: 88px;
  height: 52px;
  position: relative;
}

.schedule-grid-cell--editable {
  cursor: pointer;

  &:hover {
    outline: 2px solid #68a0f0;
    outline-offset: -2px;
  }
}

.schedule-grid-cell-label {
  display: block;
  line-height: 1.2;
  font-size: 0.72rem;
}

.schedule-grid-leave {
  display: block;
  margin-top: 2px;
  font-size: 0.65rem;
  font-weight: 700;
  color: #5c2d2d;
}

.schedule-grid-oncall .schedule-grid-name {
  background: #eee;
}

.schedule-legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem 1.25rem;
}

.schedule-legend-title {
  font-weight: 700;
}

.schedule-legend-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
}

.schedule-legend-swatch {
  width: 1rem;
  height: 1rem;
  border: 1px solid #999;
  border-radius: 2px;
}
</style>
