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

namespace OrangeHRM\Admin\Api\Model;

use OpenApi\Annotations as OA;
use OrangeHRM\Admin\Service\EmailTemplateService;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\EmailTemplate;

/**
 * @OA\Schema(
 *     schema="Admin-EmailTemplateModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(
 *         property="email",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string")
 *     ),
 *     @OA\Property(property="locale", type="string", nullable=true),
 *     @OA\Property(property="performerRole", type="string", nullable=true),
 *     @OA\Property(property="recipientRole", type="string", nullable=true),
 *     @OA\Property(property="subject", type="string"),
 *     @OA\Property(property="body", type="string"),
 *     @OA\Property(property="hasCustomContent", type="boolean"),
 *     @OA\Property(property="defaultSubjectPath", type="string"),
 *     @OA\Property(property="defaultBodyPath", type="string")
 * )
 */
class EmailTemplateModel implements Normalizable
{
    private EmailTemplate $template;
    private EmailTemplateService $emailTemplateService;

    public function __construct(EmailTemplate $template)
    {
        $this->template = $template;
        $this->emailTemplateService = new EmailTemplateService();
    }

    public function toArray(): array
    {
        $resolved = $this->emailTemplateService->resolveTemplateContent($this->template);
        return [
            'id' => $this->template->getId(),
            'email' => [
                'id' => $this->template->getEmail()->getId(),
                'name' => $this->template->getEmail()->getName(),
            ],
            'locale' => $this->template->getLocale(),
            'performerRole' => $this->template->getPerformerRole(),
            'recipientRole' => $this->template->getRecipientRole(),
            'subject' => $resolved['subject'],
            'body' => $resolved['body'],
            'hasCustomContent' => $resolved['hasCustomContent'],
            'defaultSubjectPath' => $this->template->getSubject(),
            'defaultBodyPath' => $this->template->getBody(),
        ];
    }
}
