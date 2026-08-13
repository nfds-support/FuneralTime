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

namespace OrangeHRM\Tests\Core\Utility;

use OrangeHRM\Core\Utility\HtmlSanitizer;
use OrangeHRM\Tests\Util\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testSanitizeAllowsSafeFormatting(): void
    {
        $html = '<p>Hello <strong>world</strong></p><ul><li>One</li></ul>';
        $clean = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('<strong>world</strong>', $clean);
        $this->assertStringContainsString('<li>One</li>', $clean);
    }

    public function testSanitizeRemovesScripts(): void
    {
        $html = '<p>Hi</p><script>alert(1)</script><img src=x onerror=alert(1)>';
        $clean = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('<script>', $clean ?? '');
        $this->assertStringNotContainsString('onerror', $clean ?? '');
        $this->assertStringNotContainsString('<img', $clean ?? '');
    }

    public function testSanitizeEmptyReturnsNull(): void
    {
        $this->assertNull($this->sanitizer->sanitize(null));
        $this->assertNull($this->sanitizer->sanitize(''));
        $this->assertNull($this->sanitizer->sanitize('<p><br></p>'));
        $this->assertNull($this->sanitizer->sanitize('<p>&nbsp;</p>'));
    }

    public function testPlainTextLength(): void
    {
        $this->assertSame(0, $this->sanitizer->plainTextLength(null));
        $this->assertSame(11, $this->sanitizer->plainTextLength('<p>Hello <b>world</b></p>'));
    }
}
