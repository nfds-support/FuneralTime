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

import UnionList from './pages/unions/UnionList.vue';
import SaveUnion from './pages/unions/SaveUnion.vue';
import UnionLeaveRuleList from './pages/leaveRules/UnionLeaveRuleList.vue';
import SaveUnionLeaveRule from './pages/leaveRules/SaveUnionLeaveRule.vue';
import EmployeeUnionList from './pages/employeeUnions/EmployeeUnionList.vue';
import SaveEmployeeUnion from './pages/employeeUnions/SaveEmployeeUnion.vue';

export default {
  'union-list': UnionList,
  'union-save': SaveUnion,
  'union-edit': SaveUnion,
  'union-leave-rule-list': UnionLeaveRuleList,
  'union-leave-rule-save': SaveUnionLeaveRule,
  'union-leave-rule-edit': SaveUnionLeaveRule,
  'employee-union-list': EmployeeUnionList,
  'employee-union-save': SaveEmployeeUnion,
  'employee-union-edit': SaveEmployeeUnion,
};
