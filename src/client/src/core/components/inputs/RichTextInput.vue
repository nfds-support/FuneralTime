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
  <oxd-input-group
    class="orangehrm-rich-text-input"
    :label="label"
    :label-icon="labelIcon"
    :message="message"
  >
    <div
      class="orangehrm-rich-text-input__editor"
      :class="{
        '--error': !!message,
        '--disabled': disabled,
      }"
    >
      <quill-editor
        ref="quill"
        v-model:content="content"
        content-type="html"
        theme="snow"
        :options="editorOptions"
        :disabled="disabled"
        :read-only="disabled"
        @update:content="onContentUpdate"
      />
    </div>
    <!-- Registers with oxd-form validation without being visible -->
    <oxd-input-field
      class="orangehrm-rich-text-input__hidden"
      type="textarea"
      :model-value="modelValue"
      :rules="rules"
      :required="required"
      :disabled="disabled"
      tabindex="-1"
      aria-hidden="true"
      @update:model-value="$emit('update:modelValue', normalizeHtml($event))"
    />
  </oxd-input-group>
</template>

<script>
import {QuillEditor} from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const EMPTY_PATTERNS = [
  '',
  '<p><br></p>',
  '<p></p>',
  '<p><br/></p>',
  '<p>&nbsp;</p>',
];

export default {
  name: 'RichTextInput',
  components: {
    'quill-editor': QuillEditor,
  },
  props: {
    modelValue: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: null,
    },
    labelIcon: {
      type: String,
      default: null,
    },
    required: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    rules: {
      type: Array,
      default: () => [],
    },
    placeholder: {
      type: String,
      default: '',
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      content: this.modelValue || '',
      message: '',
    };
  },
  computed: {
    editorOptions() {
      return {
        placeholder: this.placeholder || '',
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            [{list: 'ordered'}, {list: 'bullet'}],
            [{header: [1, 2, 3, false]}],
            ['link'],
            ['clean'],
          ],
        },
      };
    },
  },
  watch: {
    modelValue(value) {
      const next = value || '';
      if (this.normalizeHtml(this.content) !== this.normalizeHtml(next)) {
        this.content = next;
      }
    },
  },
  methods: {
    normalizeHtml(html) {
      if (html == null) {
        return '';
      }
      const trimmed = String(html).trim();
      if (EMPTY_PATTERNS.includes(trimmed)) {
        return '';
      }
      const plain = trimmed
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      return plain === '' ? '' : trimmed;
    },
    onContentUpdate(html) {
      const normalized = this.normalizeHtml(html);
      this.$emit('update:modelValue', normalized);
      this.runRules(normalized);
    },
    async runRules(value) {
      if (!this.rules?.length) {
        this.message = '';
        return;
      }
      for (const rule of this.rules) {
        const result = await rule(value);
        if (result !== true) {
          this.message = typeof result === 'string' ? result : '';
          return;
        }
      }
      this.message = '';
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-rich-text-input {
  width: 100%;

  &__editor {
    width: 100%;
    border: 1px solid $oxd-interface-gray-lighten-1-color;
    border-radius: 0.65rem;
    background-color: $oxd-white-color;
    overflow: hidden;

    &.--error {
      border-color: $oxd-feedback-danger-color;
    }

    &.--disabled {
      opacity: 0.65;
      pointer-events: none;
    }

    :deep(.ql-toolbar.ql-snow) {
      border: none;
      border-bottom: 1px solid $oxd-interface-gray-lighten-1-color;
      font-family: inherit;
    }

    :deep(.ql-container.ql-snow) {
      border: none;
      font-family: inherit;
      font-size: 14px;
      min-height: 120px;
    }

    :deep(.ql-editor) {
      min-height: 120px;
      line-height: 1.5;
    }

    :deep(.ql-editor.ql-blank::before) {
      font-style: normal;
      color: $oxd-interface-gray-color;
    }
  }

  &__hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
  }
}
</style>
