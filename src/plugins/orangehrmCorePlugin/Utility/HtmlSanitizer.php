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

namespace OrangeHRM\Core\Utility;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitize user-authored HTML from rich-text fields before persistence / re-display.
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * @param string|null $html
     * @return string|null
     */
    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        if ($trimmed === '' || $this->isEmptyRichText($trimmed)) {
            return null;
        }

        $clean = $this->getPurifier()->purify($trimmed);
        $clean = trim($clean);

        if ($clean === '' || $this->isEmptyRichText($clean)) {
            return null;
        }

        return $clean;
    }

    /**
     * Plain-text length after stripping tags (for validation / list previews).
     */
    public function plainTextLength(?string $html): int
    {
        if ($html === null || $html === '') {
            return 0;
        }
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        return mb_strlen($text);
    }

    private function isEmptyRichText(string $html): bool
    {
        $stripped = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $stripped === '';
    }

    private function getPurifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            $config->set(
                'HTML.Allowed',
                'p,br,strong,b,em,i,u,s,ul,ol,li,a[href|title|target|rel],h1,h2,h3,blockquote,span[style]'
            );
            $config->set('CSS.AllowedProperties', ['text-align', 'color', 'background-color']);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.TargetBlank', true);
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
            ]);
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.Linkify', false);
            $config->set('Cache.DefinitionImpl', null);
            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
