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
  <div
    v-if="hasContent"
    class="orangehrm-rich-text-display"
    v-html="html"
  ></div>
  <oxd-text v-else tag="p" class="orangehrm-rich-text-display --empty">
    {{ emptyText }}
  </oxd-text>
</template>

<script>
export default {
  name: 'RichTextDisplay',
  props: {
    html: {
      type: String,
      default: '',
    },
    emptyText: {
      type: String,
      default: '',
    },
  },
  computed: {
    hasContent() {
      if (!this.html) {
        return false;
      }
      const plain = String(this.html)
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      return plain !== '';
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-rich-text-display {
  font-size: 14px;
  line-height: 1.5;
  color: $oxd-interface-gray-darken-1-color;
  word-break: break-word;

  &.--empty {
    color: $oxd-interface-gray-color;
  }

  :deep(p) {
    margin: 0 0 0.5rem;
  }

  :deep(ul),
  :deep(ol) {
    margin: 0 0 0.5rem;
    padding-left: 1.25rem;
  }

  :deep(a) {
    color: $oxd-primary-one-color;
  }

  :deep(h1),
  :deep(h2),
  :deep(h3) {
    margin: 0 0 0.5rem;
    font-weight: 700;
  }
}
</style>
