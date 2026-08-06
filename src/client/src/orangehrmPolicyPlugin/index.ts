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

import PolicyList from './pages/policies/PolicyList.vue';
import SavePolicy from './pages/policies/SavePolicy.vue';
import PolicyAcknowledgmentList from './pages/policies/PolicyAcknowledgmentList.vue';
import MyPolicyList from './pages/myPolicies/MyPolicyList.vue';
import MoodleConfig from './pages/moodle/MoodleConfig.vue';
import LearningPortal from './pages/learning/LearningPortal.vue';

export default {
  'policy-list': PolicyList,
  'policy-save': SavePolicy,
  'policy-edit': SavePolicy,
  'policy-acknowledgment-list': PolicyAcknowledgmentList,
  'my-policy-list': MyPolicyList,
  'moodle-config': MoodleConfig,
  'learning-portal': LearningPortal,
};
