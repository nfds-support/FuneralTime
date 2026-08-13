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

namespace OrangeHRM\Core\Traits;

use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Utility\HtmlSanitizer;

trait HtmlSanitizerTrait
{
    private ?HtmlSanitizer $htmlSanitizer = null;

    protected function getHtmlSanitizer(): HtmlSanitizer
    {
        if (!$this->htmlSanitizer instanceof HtmlSanitizer) {
            $this->htmlSanitizer = new HtmlSanitizer();
        }
        return $this->htmlSanitizer;
    }

    /**
     * Read a body string and return sanitized rich-text HTML (or null when empty).
     */
    protected function getSanitizedRichTextOrNull(string $paramName): ?string
    {
        $raw = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            $paramName
        );
        return $this->getHtmlSanitizer()->sanitize($raw);
    }

    /**
     * When the body includes $paramName, sanitize and return it; otherwise keep $current.
     * Distinguishes "field omitted" from "field cleared".
     */
    protected function getSanitizedRichTextOrKeep(?string $current, string $paramName): ?string
    {
        if (!$this->getRequestParams()->has(RequestParams::PARAM_TYPE_BODY, $paramName)) {
            return $current;
        }
        return $this->getSanitizedRichTextOrNull($paramName);
    }

    /**
     * Sanitize `comment` keys inside a ratings payload array.
     *
     * @param array $ratings
     * @return array
     */
    protected function sanitizeRichTextCommentsInRatings(array $ratings): array
    {
        foreach ($ratings as $index => $rating) {
            if (!is_array($rating)) {
                continue;
            }
            if (array_key_exists('comment', $rating)) {
                $ratings[$index]['comment'] = $this->getHtmlSanitizer()->sanitize(
                    is_string($rating['comment']) ? $rating['comment'] : null
                );
            }
        }
        return $ratings;
    }
}
