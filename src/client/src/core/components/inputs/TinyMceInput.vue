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
    class="orangehrm-tinymce-input"
    :label="label"
    :label-icon="labelIcon"
    :message="message"
  >
    <div
      class="orangehrm-tinymce-input__editor"
      :class="{
        '--error': !!message,
        '--disabled': disabled,
      }"
    >
      <editor
        :id="editorId"
        v-model="content"
        :disabled="disabled"
        :init="editorInit"
        license-key="gpl"
        @update:model-value="onContentUpdate"
      />
    </div>
    <oxd-input-field
      class="orangehrm-tinymce-input__hidden"
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
import Editor from '@tinymce/tinymce-vue';
import tinymce from 'tinymce/tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/code';
import 'tinymce/plugins/table';
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/skins/content/default/content.min.css';

const EMPTY_PATTERNS = [
  '',
  '<p><br></p>',
  '<p></p>',
  '<p><br/></p>',
  '<p>&nbsp;</p>',
];

let editorSeq = 0;

export default {
  name: 'TinyMceInput',
  components: {
    editor: Editor,
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
    height: {
      type: Number,
      default: 320,
    },
  },
  emits: ['update:modelValue'],
  data() {
    editorSeq += 1;
    return {
      editorId: `ohrm-tinymce-${editorSeq}`,
      content: this.modelValue || '',
      message: '',
      tinymce,
    };
  },
  computed: {
    editorInit() {
      return {
        height: this.height,
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: true,
        resize: true,
        skin: false,
        content_css: false,
        plugins: 'lists link code table',
        toolbar:
          'undo redo | styles | bold italic underline | ' +
          'alignleft aligncenter alignright | bullist numlist | ' +
          'link table | code removeformat',
        placeholder: this.placeholder || '',
        convert_urls: false,
        relative_urls: false,
        entity_encoding: 'raw',
        valid_elements: '*[*]',
        extended_valid_elements: '*[*]',
        content_style:
          'body { font-family: Inherit, Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
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
.orangehrm-tinymce-input {
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

    :deep(.tox-tinymce) {
      border: none;
      border-radius: 0.65rem;
    }

    :deep(.tox-editor-header) {
      box-shadow: none;
      border-bottom: 1px solid $oxd-interface-gray-lighten-1-color;
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
