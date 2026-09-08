<?php

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

namespace OrangeHRM\Core\Service;

use OrangeHRM\Admin\Dto\I18NLanguageSearchFilterParams;
use OrangeHRM\Admin\Traits\Service\LocalizationServiceTrait;
use OrangeHRM\Authentication\Auth\User as AuthUser;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\I18N\Traits\Service\I18NHelperTrait;

class UiLanguageService
{
    use AuthUserTrait;
    use ConfigServiceTrait;
    use LocalizationServiceTrait;
    use I18NHelperTrait;

    public const ALLOWED_CODES = ['en_US', 'fr_CA'];

    /**
     * @return array<int, array{id:int|null, name:string, code:string}>
     */
    public function getSelectableLanguages(): array
    {
        $search = new I18NLanguageSearchFilterParams();
        $search->setAddedOnly(true);
        $search->setEnabledOnly(true);
        $languages = $this->getLocalizationService()->searchLanguages($search);

        $byCode = [];
        foreach ($languages as $language) {
            $byCode[$language->getCode()] = [
                'id' => $language->getId(),
                'name' => $language->getName(),
                'code' => $language->getCode(),
            ];
        }

        $result = [];
        foreach (self::ALLOWED_CODES as $code) {
            if (isset($byCode[$code])) {
                $result[] = $byCode[$code];
            } else {
                $result[] = [
                    'id' => null,
                    'name' => $code === 'fr_CA'
                        ? 'French (Canada) - Français (Canada)'
                        : 'English (United States)',
                    'code' => $code,
                ];
            }
        }
        return $result;
    }

    public function resolveCurrentLanguage(?Request $request = null): string
    {
        $sessionLang = $this->getAuthUser()->getAttribute(AuthUser::USER_UI_LANGUAGE);
        if (is_string($sessionLang) && $this->isAllowed($sessionLang)) {
            return $sessionLang;
        }

        if ($request instanceof Request) {
            $cookieLang = $request->cookies->get(AuthUser::UI_LANGUAGE_COOKIE);
            if (is_string($cookieLang) && $this->isAllowed($cookieLang)) {
                $this->getAuthUser()->setAttribute(AuthUser::USER_UI_LANGUAGE, $cookieLang);
                return $cookieLang;
            }
        }

        $default = $this->getConfigService()->getAdminLocalizationDefaultLanguage();
        return $this->isAllowed($default) ? $default : 'en_US';
    }

    public function setCurrentLanguage(string $langCode, ?Request $request = null): string
    {
        if (!$this->isAllowed($langCode)) {
            $langCode = 'en_US';
        }
        $this->getAuthUser()->setAttribute(AuthUser::USER_UI_LANGUAGE, $langCode);
        $this->getI18NHelper()->setTranslatorLanguage($langCode);
        return $langCode;
    }

    public function isAllowed(string $langCode): bool
    {
        return in_array($langCode, self::ALLOWED_CODES, true);
    }

    public function isFrench(?string $langCode = null): bool
    {
        $langCode ??= $this->resolveCurrentLanguage();
        return str_starts_with($langCode, 'fr');
    }
}
