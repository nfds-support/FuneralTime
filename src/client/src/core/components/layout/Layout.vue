<template>
  <oxd-layout v-bind="$attrs">
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData" />
    </template>
    <template #user-actions>
      <li v-for="language in uiLanguages" :key="language.code">
        <a
          href="#"
          role="menuitem"
          class="oxd-userdropdown-link"
          @click.prevent="onSelectLanguage(language.code)"
        >
          {{ languageLabel(language) }}
          <span v-if="language.code === currentUiLanguage"> ✓</span>
        </a>
      </li>
      <li>
        <a
          href="#"
          role="menuitem"
          class="oxd-userdropdown-link"
          @click="openAboutModel"
        >
          {{ $t('general.about') }}
        </a>
      </li>
      <li>
        <a :href="supportUrl" role="menuitem" class="oxd-userdropdown-link">
          {{ $t('general.support') }}
        </a>
      </li>
      <li v-if="updatePasswordUrl">
        <a
          :href="updatePasswordUrl"
          role="menuitem"
          class="oxd-userdropdown-link"
        >
          {{ $t('general.change_password') }}
        </a>
      </li>
      <li>
        <a :href="logoutUrl" role="menuitem" class="oxd-userdropdown-link">
          {{ $t('general.logout') }}
        </a>
      </li>
    </template>
    <template #nav-actions>
      <oxd-icon-button
        name="question-lg"
        :title="$t('general.help')"
        @click="onClickSupport"
      />
    </template>
  </oxd-layout>
  <about v-if="showAboutModel" @close="closeAboutModel"></about>
</template>

<script>
import {provide, readonly, ref} from 'vue';
import About from '@/core/pages/About.vue';
import {OxdLayout} from '@ohrm/oxd';
import {APIService} from '@/core/util/services/api.service';
import {dateFormatKey} from '@/core/util/composable/useDateFormat';
import {reloadPage} from '@/core/util/helper/navigation';

export default {
  components: {
    about: About,
    'oxd-layout': OxdLayout,
  },
  inheritAttrs: false,
  props: {
    permissions: {
      type: Object,
      default: () => ({}),
    },
    logoutUrl: {
      type: String,
      default: '#',
    },
    supportUrl: {
      type: String,
      default: '#',
    },
    updatePasswordUrl: {
      type: String,
      default: '#',
    },
    dateFormat: {
      type: Object,
      default: null,
    },
    helpUrl: {
      type: String,
      default: null,
    },
    uiLanguages: {
      type: Array,
      default: () => [],
    },
    currentUiLanguage: {
      type: String,
      default: 'en_US',
    },
  },
  setup(props) {
    const showAboutModel = ref(false);
    provide('permissions', readonly(props.permissions));
    provide(dateFormatKey, readonly(props.dateFormat));

    const openAboutModel = () => {
      showAboutModel.value = true;
    };

    const closeAboutModel = () => {
      showAboutModel.value = false;
    };

    const onClickSupport = () => {
      if (props.helpUrl) window.open(props.helpUrl, '_blank');
    };

    const languageLabel = (language) => {
      if (language.code === 'fr_CA') {
        return 'Français (Canada)';
      }
      if (language.code === 'en_US') {
        return 'English (United States)';
      }
      return language.name || language.code;
    };

    const onSelectLanguage = async (code) => {
      if (code === props.currentUiLanguage) {
        return;
      }
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/core/ui-language',
      );
      await http.update(0, {language: code});
      document.cookie = `ft_ui_language=${encodeURIComponent(
        code,
      )};path=/;max-age=31536000;SameSite=Lax`;
      reloadPage();
    };

    return {
      onClickSupport,
      showAboutModel,
      openAboutModel,
      closeAboutModel,
      languageLabel,
      onSelectLanguage,
    };
  },
};
</script>
