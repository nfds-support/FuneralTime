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
  <div class="orangehrm-side-by-side orangehrm-card-container">
    <oxd-text class="orangehrm-main-title">
      {{ $t('performance.side_by_side_assessment') }}
    </oxd-text>
    <oxd-divider />

    <div class="orangehrm-side-by-side-header">
      <div>
        <oxd-text tag="p" class="orangehrm-side-by-side-column-title">
          {{ $t('performance.self_assessment') }}
        </oxd-text>
        <div class="orangehrm-side-by-side-reviewer">
          <img
            class="orangehrm-side-by-side-reviewer-profile-image"
            alt="profile picture"
            :src="employeeProfileSrc"
          />
          <div class="orangehrm-side-by-side-reviewer-name">
            <oxd-text type="card-title">
              {{ employeeName }}
            </oxd-text>
            <oxd-text type="card-body">
              {{ employeeJobTitle }}
            </oxd-text>
          </div>
        </div>
        <oxd-text type="card-body" class="orangehrm-side-by-side-status">
          {{ $t('general.status') }}: {{ employeeStatusLabel }}
        </oxd-text>
      </div>
      <div>
        <oxd-text tag="p" class="orangehrm-side-by-side-column-title">
          {{ $t('performance.manager_assessment') }}
        </oxd-text>
        <div class="orangehrm-side-by-side-reviewer">
          <img
            class="orangehrm-side-by-side-reviewer-profile-image"
            alt="profile picture"
            :src="supervisorProfileSrc"
          />
          <div class="orangehrm-side-by-side-reviewer-name">
            <oxd-text type="card-title">
              {{ supervisorName }}
            </oxd-text>
            <oxd-text type="card-body">
              {{ supervisorJobTitle }}
            </oxd-text>
          </div>
        </div>
        <oxd-text type="card-body" class="orangehrm-side-by-side-status">
          {{ $t('general.status') }}: {{ supervisorStatusLabel }}
        </oxd-text>
      </div>
    </div>

    <div
      v-for="(kpi, index) in kpis"
      :key="kpi.id"
      class="orangehrm-side-by-side-kpi"
    >
      <oxd-text tag="p" class="orangehrm-side-by-side-kpi-title" :title="kpi.title">
        {{ kpi.title }}
      </oxd-text>
      <oxd-text tag="p" class="orangehrm-side-by-side-kpi-minmax">
        {{ $t('performance.min') }}: {{ kpi.minRating }}
        &nbsp;|&nbsp;
        {{ $t('performance.max') }}: {{ kpi.maxRating }}
      </oxd-text>
      <div
        v-if="hasRubric(kpi)"
        class="orangehrm-side-by-side-kpi-rubric"
      >
        <oxd-text
          v-for="(level, levelIndex) in kpi.ratingRubric"
          :key="`${kpi.id}-rubric-${levelIndex}`"
          tag="span"
          class="orangehrm-side-by-side-kpi-rubric-item"
        >
          {{ formatRubricLevel(level) }}
        </oxd-text>
      </div>

      <div class="orangehrm-side-by-side-kpi-columns">
        <div class="orangehrm-side-by-side-kpi-side">
          <template v-if="showEmployeeAssessment">
            <oxd-text type="subtitle-2">{{ $t('performance.rating') }}</oxd-text>
            <oxd-input-field
              v-if="usesRubricSelect(kpi)"
              type="select"
              :options="getRubricOptions(kpi)"
              :disabled="!employeeEditable"
              :model-value="getSelectedRubricOption(kpi, employeeReview.kpis[index].rating)"
              @update:model-value="
                onUpdateEmployeeRating(extractRubricRating($event), index)
              "
            />
            <oxd-input-field
              v-else
              type="input"
              :disabled="!employeeEditable"
              :rules="rules[index]"
              :model-value="employeeReview.kpis[index].rating"
              @update:model-value="onUpdateEmployeeRating($event, index)"
            />
            <oxd-text type="subtitle-2">{{ $t('general.comment') }}</oxd-text>
            <rich-text-input
              :disabled="!employeeEditable"
              :rules="commentValidators"
              :model-value="employeeReview.kpis[index].comment"
              @update:model-value="onUpdateEmployeeComment($event, index)"
            />
          </template>
          <oxd-text
            v-else
            tag="p"
            class="orangehrm-side-by-side-kpi-placeholder"
          >
            {{ $t('performance.self_assessment_placeholder') }}
          </oxd-text>
        </div>

        <div class="orangehrm-side-by-side-kpi-side">
          <template v-if="showSupervisorAssessment">
            <oxd-text type="subtitle-2">{{ $t('performance.rating') }}</oxd-text>
            <oxd-input-field
              v-if="usesRubricSelect(kpi)"
              type="select"
              :options="getRubricOptions(kpi)"
              :disabled="!supervisorEditable"
              :model-value="getSelectedRubricOption(kpi, supervisorReview.kpis[index].rating)"
              @update:model-value="
                onUpdateSupervisorRating(extractRubricRating($event), index)
              "
            />
            <oxd-input-field
              v-else
              type="input"
              :disabled="!supervisorEditable"
              :rules="rules[index]"
              :model-value="supervisorReview.kpis[index].rating"
              @update:model-value="onUpdateSupervisorRating($event, index)"
            />
            <oxd-text type="subtitle-2">{{ $t('general.comment') }}</oxd-text>
            <rich-text-input
              :disabled="!supervisorEditable"
              :rules="commentValidators"
              :model-value="supervisorReview.kpis[index].comment"
              @update:model-value="onUpdateSupervisorComment($event, index)"
            />
          </template>
          <oxd-text
            v-else
            tag="p"
            class="orangehrm-side-by-side-kpi-placeholder"
          >
            {{ $t('performance.manager_assessment_placeholder') }}
          </oxd-text>
        </div>
      </div>
    </div>

    <div class="orangehrm-side-by-side-general">
      <oxd-text tag="p" class="orangehrm-side-by-side-general-label">
        {{ $t('performance.general_comment') }}
      </oxd-text>
      <div class="orangehrm-side-by-side-general-columns">
        <div>
          <rich-text-input
            v-if="showEmployeeAssessment"
            :disabled="!employeeEditable"
            :rules="commentValidators"
            :model-value="employeeReview.generalComment"
            @update:model-value="onUpdateEmployeeGeneralComment($event)"
          />
          <oxd-text
            v-else
            tag="p"
            class="orangehrm-side-by-side-kpi-placeholder"
          >
            {{ $t('performance.self_assessment_placeholder') }}
          </oxd-text>
        </div>
        <div>
          <rich-text-input
            v-if="showSupervisorAssessment"
            :disabled="!supervisorEditable"
            :rules="commentValidators"
            :model-value="supervisorReview.generalComment"
            @update:model-value="onUpdateSupervisorGeneralComment($event)"
          />
          <oxd-text
            v-else
            tag="p"
            class="orangehrm-side-by-side-kpi-placeholder"
          >
            {{ $t('performance.manager_assessment_placeholder') }}
          </oxd-text>
        </div>
      </div>
    </div>

    <slot></slot>
  </div>
</template>

<script>
import {computed} from 'vue';
import usei18n from '@/core/util/composable/usei18n';
import {shouldNotExceedPlainTextLength} from '@/core/util/validation/rules';
import useEmployeeNameTranslate from '@/core/util/composable/useEmployeeNameTranslate';
import {OxdDivider} from '@ohrm/oxd';

const defaultPic = `${window.appGlobal.publicPath}/images/default-photo.png`;
const EVALUATION_COMPLETED = 3;

export default {
  name: 'SideBySideEvaluation',
  components: {
    'oxd-divider': OxdDivider,
  },
  props: {
    kpis: {
      type: Array,
      required: true,
    },
    employeeReview: {
      type: Object,
      required: true,
      validator: (value) =>
        Object.hasOwn(value, 'kpis') && Object.hasOwn(value, 'generalComment'),
    },
    supervisorReview: {
      type: Object,
      required: true,
      validator: (value) =>
        Object.hasOwn(value, 'kpis') && Object.hasOwn(value, 'generalComment'),
    },
    employeeEditable: {
      type: Boolean,
      required: true,
    },
    supervisorEditable: {
      type: Boolean,
      required: true,
    },
    rules: {
      type: Array,
      required: true,
    },
    employee: {
      type: Object,
      required: true,
    },
    supervisor: {
      type: Object,
      required: true,
    },
    employeeJobTitle: {
      type: String,
      required: true,
    },
    supervisorJobTitle: {
      type: String,
      required: true,
    },
    employeeStatus: {
      type: Number,
      required: true,
    },
    supervisorStatus: {
      type: Number,
      required: true,
    },
  },
  emits: ['update:employeeReview', 'update:supervisorReview'],
  setup(props, context) {
    const {$t} = usei18n();
    const {$tEmpName} = useEmployeeNameTranslate();
    const commentValidators = [shouldNotExceedPlainTextLength(2000)];

    const statusOpts = [
      {id: 1, label: $t('performance.evaluation_activated')},
      {id: 2, label: $t('performance.evaluation_in_progress')},
      {id: 3, label: $t('performance.evaluation_completed')},
    ];

    const employeeProfileSrc = computed(() => {
      return props.employee.empNumber
        ? `${window.appGlobal.baseUrl}/pim/viewPhoto/empNumber/${props.employee.empNumber}`
        : defaultPic;
    });

    const supervisorProfileSrc = computed(() => {
      return props.supervisor.empNumber
        ? `${window.appGlobal.baseUrl}/pim/viewPhoto/empNumber/${props.supervisor.empNumber}`
        : defaultPic;
    });

    const employeeName = computed(() => $tEmpName(props.employee));
    const supervisorName = computed(() => $tEmpName(props.supervisor));

    const employeeStatusLabel = computed(
      () => statusOpts.find((el) => el.id === props.employeeStatus)?.label || '',
    );
    const supervisorStatusLabel = computed(
      () =>
        statusOpts.find((el) => el.id === props.supervisorStatus)?.label || '',
    );

    const showEmployeeAssessment = computed(
      () =>
        props.employeeEditable ||
        props.employeeStatus >= EVALUATION_COMPLETED,
    );

    const showSupervisorAssessment = computed(
      () =>
        props.supervisorEditable ||
        props.supervisorStatus >= EVALUATION_COMPLETED,
    );

    const hasRubric = (kpi) =>
      Array.isArray(kpi.ratingRubric) && kpi.ratingRubric.length > 0;

    const formatRubricLevel = (level) => {
      const base = `${level.rating} - ${level.label}`;
      return level.description ? `${base}: ${level.description}` : base;
    };

    const usesRubricSelect = (kpi) => {
      if (!hasRubric(kpi)) {
        return false;
      }
      const ratings = new Set(
        kpi.ratingRubric.map((level) => Number(level.rating)),
      );
      for (let rating = kpi.minRating; rating <= kpi.maxRating; rating++) {
        if (!ratings.has(rating)) {
          return false;
        }
      }
      return true;
    };

    const getRubricOptions = (kpi) => {
      return [...kpi.ratingRubric]
        .sort((a, b) => Number(a.rating) - Number(b.rating))
        .map((level) => ({
          id: Number(level.rating),
          label: `${level.rating} - ${level.label}`,
        }));
    };

    const getSelectedRubricOption = (kpi, rating) => {
      if (rating === null || rating === undefined || rating === '') {
        return null;
      }
      return (
        getRubricOptions(kpi).find(
          (option) => Number(option.id) === Number(rating),
        ) || null
      );
    };

    const extractRubricRating = (option) => {
      if (option === null || option === undefined) {
        return null;
      }
      return typeof option === 'object' ? option.id : option;
    };

    const emitEmployeeReview = (kpis, generalComment) => {
      context.emit('update:employeeReview', {
        kpis,
        generalComment,
      });
    };

    const emitSupervisorReview = (kpis, generalComment) => {
      context.emit('update:supervisorReview', {
        kpis,
        generalComment,
      });
    };

    const onUpdateEmployeeRating = (value, index) => {
      emitEmployeeReview(
        props.employeeReview.kpis.map((item, _index) => {
          if (_index === index) {
            return {...item, rating: value};
          }
          return item;
        }),
        props.employeeReview.generalComment,
      );
    };

    const onUpdateEmployeeComment = (value, index) => {
      emitEmployeeReview(
        props.employeeReview.kpis.map((item, _index) => {
          if (_index === index) {
            return {...item, comment: value};
          }
          return item;
        }),
        props.employeeReview.generalComment,
      );
    };

    const onUpdateEmployeeGeneralComment = (value) => {
      emitEmployeeReview(props.employeeReview.kpis, value);
    };

    const onUpdateSupervisorRating = (value, index) => {
      emitSupervisorReview(
        props.supervisorReview.kpis.map((item, _index) => {
          if (_index === index) {
            return {...item, rating: value};
          }
          return item;
        }),
        props.supervisorReview.generalComment,
      );
    };

    const onUpdateSupervisorComment = (value, index) => {
      emitSupervisorReview(
        props.supervisorReview.kpis.map((item, _index) => {
          if (_index === index) {
            return {...item, comment: value};
          }
          return item;
        }),
        props.supervisorReview.generalComment,
      );
    };

    const onUpdateSupervisorGeneralComment = (value) => {
      emitSupervisorReview(props.supervisorReview.kpis, value);
    };

    return {
      commentValidators,
      employeeProfileSrc,
      supervisorProfileSrc,
      employeeName,
      supervisorName,
      employeeStatusLabel,
      supervisorStatusLabel,
      showEmployeeAssessment,
      showSupervisorAssessment,
      hasRubric,
      formatRubricLevel,
      usesRubricSelect,
      getRubricOptions,
      getSelectedRubricOption,
      extractRubricRating,
      onUpdateEmployeeRating,
      onUpdateEmployeeComment,
      onUpdateEmployeeGeneralComment,
      onUpdateSupervisorRating,
      onUpdateSupervisorComment,
      onUpdateSupervisorGeneralComment,
    };
  },
};
</script>

<style src="./side-by-side-evaluation.scss" lang="scss" scoped></style>
