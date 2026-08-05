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

namespace OrangeHRM\CorporateBranding\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Dto\Base64Attachment;
use OrangeHRM\Core\Traits\ValidatorTrait;
use OrangeHRM\CorporateBranding\Api\Model\ThemeModel;
use OrangeHRM\CorporateBranding\Api\Traits\VariablesParamRuleCollection;
use OrangeHRM\CorporateBranding\Api\ValidationRules\ImageAspectRatio;
use OrangeHRM\CorporateBranding\Dto\PartialTheme;
use OrangeHRM\CorporateBranding\Service\ThemeService;
use OrangeHRM\CorporateBranding\Traits\ThemeServiceTrait;
use OrangeHRM\Entity\Theme;

class ThemeAPI extends Endpoint implements ResourceEndpoint
{
    use ThemeServiceTrait;
    use ValidatorTrait;
    use VariablesParamRuleCollection;

    public const PARAMETER_VARIABLES = 'variables';
    public const PARAMETER_SHOW_SOCIAL_MEDIA_ICONS = 'showSocialMediaImages';
    public const PARAMETER_CLIENT_LOGO = 'clientLogo';
    public const PARAMETER_CLIENT_BANNER = 'clientBanner';
    public const PARAMETER_LOGIN_BANNER = 'loginBanner';
    public const PARAMETER_CURRENT_CLIENT_LOGO = 'currentClientLogo';
    public const PARAMETER_CURRENT_CLIENT_BANNER = 'currentClientBanner';
    public const PARAMETER_CURRENT_LOGIN_BANNER = 'currentLoginBanner';

    public const KEEP_CURRENT = 'keepCurrent';
    public const DELETE_CURRENT = 'deleteCurrent';
    public const REPLACE_CURRENT = 'replaceCurrent';

    public const PARAM_RULE_FILE_NAME_MAX_LENGTH = 100;

    /**
     * @OA\Get(
     *     path="/api/v2/admin/theme",
     *     tags={"Admin/Theme"},
     *     summary="Get Theme Details",
     *     operationId="get-theme-details",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CorporateBranding-ThemeModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $theme = $this->getThemeService()
            ->getThemeDao()
            ->getPartialThemeByThemeName(ThemeService::CUSTOM_THEME);

        if (!$theme instanceof PartialTheme) {
            $theme = $this->getThemeService()
                ->getThemeDao()
                ->getPartialThemeByThemeName();
        }

        return new EndpointResourceResult(ThemeModel::class, $theme);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection();
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
    }

    /**
     * @OA\Put(
     *     path="/api/v2/admin/theme",
     *     tags={"Admin/Theme"},
     *     summary="Edit Theme",
     *     operationId="edit-theme",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="variables", type="object",
     *                 @OA\Property(property="primaryColor", type="string", example="#FF7B1D"),
     *                 @OA\Property(property="primaryFontColor", type="string", example="#FFFFFF"),
     *                 @OA\Property(property="secondaryColor", type="string", example="#76BC21"),
     *                 @OA\Property(property="secondaryFontColor", type="string", example="#FFFFFF"),
     *                 @OA\Property(property="primaryGradientStartColor", type="string", example="#FF920B"),
     *                 @OA\Property(property="primaryGradientEndColor", type="string", example="#F35C17"),
     *                 required={"primaryColor", "primaryFontColor", "secondaryColor", "secondaryFontColor", "primaryGradientStartColor", "primaryGradientEndColor"}
     *             ),
     *             @OA\Property(property="showSocialMediaImages", type="boolean", example=true),
     *             @OA\Property(property="clientLogo", ref="#/components/schemas/Base64Attachment"),
     *             @OA\Property(property="clientBanner", ref="#/components/schemas/Base64Attachment"),
     *             @OA\Property(property="loginBanner", ref="#/components/schemas/Base64Attachment"),
     *             @OA\Property(
     *                 property="currentClientLogo",
     *                 type="string",
     *                 nullable=true,
     *                 enum={
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::KEEP_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::DELETE_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::REPLACE_CURRENT,
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="currentClientBanner",
     *                 type="string",
     *                 nullable=true,
     *                 enum={
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::KEEP_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::DELETE_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::REPLACE_CURRENT,
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="currentLoginBanner",
     *                 type="string",
     *                 nullable=true,
     *                 enum={
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::KEEP_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::DELETE_CURRENT,
     *                     OrangeHRM\CorporateBranding\Api\ThemeAPI::REPLACE_CURRENT,
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CorporateBranding-ThemeModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $variables = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_VARIABLES);
        $this->validate($variables, $this->getParamRuleCollection());

        $showSocialMediaIcons = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SHOW_SOCIAL_MEDIA_ICONS,
            true
        );

        $existingTheme = $this->getThemeService()
            ->getThemeDao()
            ->getPartialThemeByThemeName(ThemeService::CUSTOM_THEME);

        $fields = [
            'variables' => $variables,
            'showSocialMediaIcons' => $showSocialMediaIcons,
        ];

        $this->applyThemeImageUpdate(
            $fields,
            self::PARAMETER_CURRENT_CLIENT_LOGO,
            self::PARAMETER_CLIENT_LOGO,
            'clientLogo',
            'clientLogoFilename',
            'clientLogoFileType',
            'clientLogoFileSize'
        );
        $this->applyThemeImageUpdate(
            $fields,
            self::PARAMETER_CURRENT_CLIENT_BANNER,
            self::PARAMETER_CLIENT_BANNER,
            'clientBanner',
            'clientBannerFilename',
            'clientBannerFileType',
            'clientBannerFileSize'
        );
        $this->applyThemeImageUpdate(
            $fields,
            self::PARAMETER_CURRENT_LOGIN_BANNER,
            self::PARAMETER_LOGIN_BANNER,
            'loginBanner',
            'loginBannerFilename',
            'loginBannerFileType',
            'loginBannerFileSize'
        );

        if (!$existingTheme instanceof PartialTheme) {
            $theme = new Theme();
            $theme->setName(ThemeService::CUSTOM_THEME);
            $theme->setVariables($variables);
            $theme->setShowSocialMediaIcons($showSocialMediaIcons);
            if (array_key_exists('clientLogo', $fields)) {
                $theme->setClientLogo($fields['clientLogo']);
                $theme->setClientLogoFilename($fields['clientLogoFilename']);
                $theme->setClientLogoFileType($fields['clientLogoFileType']);
                $theme->setClientLogoFileSize($fields['clientLogoFileSize']);
            }
            if (array_key_exists('clientBanner', $fields)) {
                $theme->setClientBanner($fields['clientBanner']);
                $theme->setClientBannerFilename($fields['clientBannerFilename']);
                $theme->setClientBannerFileType($fields['clientBannerFileType']);
                $theme->setClientBannerFileSize($fields['clientBannerFileSize']);
            }
            if (array_key_exists('loginBanner', $fields)) {
                $theme->setLoginBanner($fields['loginBanner']);
                $theme->setLoginBannerFilename($fields['loginBannerFilename']);
                $theme->setLoginBannerFileType($fields['loginBannerFileType']);
                $theme->setLoginBannerFileSize($fields['loginBannerFileSize']);
            }
            $this->getThemeService()->getThemeDao()->saveTheme($theme);
            $partialTheme = PartialTheme::createFromTheme($theme);
        } else {
            $this->getThemeService()
                ->getThemeDao()
                ->updateThemeFieldsByThemeName(ThemeService::CUSTOM_THEME, $fields);
            $partialTheme = $this->getThemeService()
                ->getThemeDao()
                ->getPartialThemeByThemeName(ThemeService::CUSTOM_THEME);
        }

        $this->getThemeService()->resetThemeCache();

        return new EndpointResourceResult(ThemeModel::class, $partialTheme);
    }

    /**
     * @param array<string, mixed> $fields
     * @param string $currentParam
     * @param string $attachmentParam
     * @param string $contentField
     * @param string $filenameField
     * @param string $fileTypeField
     * @param string $fileSizeField
     */
    private function applyThemeImageUpdate(
        array &$fields,
        string $currentParam,
        string $attachmentParam,
        string $contentField,
        string $filenameField,
        string $fileTypeField,
        string $fileSizeField
    ): void {
        $current = $this->getRequestParams()
            ->getStringOrNull(RequestParams::PARAM_TYPE_BODY, $currentParam);
        if (is_null($current) || $current === self::REPLACE_CURRENT) {
            $attachment = $this->getRequestParams()->getAttachmentOrNull(
                RequestParams::PARAM_TYPE_BODY,
                $attachmentParam
            );
            if ($attachment instanceof Base64Attachment) {
                $fields[$contentField] = $attachment->getContent();
                $fields[$filenameField] = $attachment->getFilename();
                $fields[$fileTypeField] = $attachment->getFileType();
                $fields[$fileSizeField] = (int)$attachment->getSize();
            }
        } elseif ($current === self::DELETE_CURRENT) {
            $fields[$contentField] = null;
            $fields[$filenameField] = null;
            $fields[$fileTypeField] = null;
            $fields[$fileSizeField] = null;
        } // else self::KEEP_CURRENT — omit image fields so existing blobs are untouched
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $currentAttachmentRule = new Rule(
            Rules::IN,
            [[self::REPLACE_CURRENT, self::KEEP_CURRENT, self::DELETE_CURRENT]]
        );
        $paramRules = new ParamRuleCollection(
            new ParamRule(self::PARAMETER_VARIABLES, new Rule(Rules::ARRAY_TYPE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_SHOW_SOCIAL_MEDIA_ICONS, new Rule(Rules::BOOL_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_CURRENT_CLIENT_LOGO, $currentAttachmentRule)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_CURRENT_CLIENT_BANNER, clone $currentAttachmentRule)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_CURRENT_LOGIN_BANNER, clone $currentAttachmentRule)
            ),
        );

        $currentClientLogo = $this->getRequestParams()
            ->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CURRENT_CLIENT_LOGO);
        $currentClientBanner = $this->getRequestParams()
            ->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CURRENT_CLIENT_BANNER);
        $currentLoginBanner = $this->getRequestParams()
            ->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CURRENT_LOGIN_BANNER);
        if (is_null($currentClientLogo)) {
            $paramRules->addParamValidation(
                $this->getValidationDecorator()->notRequiredParamRule(
                    $this->getAttachmentParamRule(
                        self::PARAMETER_CLIENT_LOGO,
                        Theme::CLIENT_LOGO_ASPECT_RATIO
                    )
                )
            );
        } elseif ($currentClientLogo === self::REPLACE_CURRENT) {
            $paramRules->addParamValidation(
                $this->getAttachmentParamRule(self::PARAMETER_CLIENT_LOGO, Theme::CLIENT_LOGO_ASPECT_RATIO)
            );
        }

        if (is_null($currentClientBanner)) {
            $paramRules->addParamValidation(
                $this->getValidationDecorator()->notRequiredParamRule(
                    $this->getAttachmentParamRule(
                        self::PARAMETER_CLIENT_BANNER,
                        Theme::CLIENT_BANNER_ASPECT_RATIO
                    )
                )
            );
        } elseif ($currentClientBanner === self::REPLACE_CURRENT) {
            $paramRules->addParamValidation(
                $this->getAttachmentParamRule(
                    self::PARAMETER_CLIENT_BANNER,
                    Theme::CLIENT_BANNER_ASPECT_RATIO
                )
            );
        }

        if (is_null($currentLoginBanner)) {
            $paramRules->addParamValidation(
                $this->getValidationDecorator()->notRequiredParamRule(
                    $this->getAttachmentParamRule(
                        self::PARAMETER_LOGIN_BANNER,
                        Theme::LOGIN_BANNER_ASPECT_RATIO
                    )
                )
            );
        } elseif ($currentLoginBanner === self::REPLACE_CURRENT) {
            $paramRules->addParamValidation(
                $this->getAttachmentParamRule(self::PARAMETER_LOGIN_BANNER, Theme::LOGIN_BANNER_ASPECT_RATIO)
            );
        }

        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
    }

    /**
     * @param string $paramKey
     * @return ParamRule
     */
    private function getAttachmentParamRule(string $paramKey, float $aspectRatio): ParamRule
    {
        return new ParamRule(
            $paramKey,
            new Rule(
                Rules::BASE_64_ATTACHMENT,
                [Theme::ALLOWED_IMAGE_TYPES, Theme::ALLOWED_IMAGE_EXTENSIONS, self::PARAM_RULE_FILE_NAME_MAX_LENGTH]
            ),
            new Rule(ImageAspectRatio::class, [$aspectRatio]),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/admin/theme",
     *     tags={"Admin/Theme"},
     *     summary="Reset Theme",
     *     operationId="reset-theme",
     *     @OA\Parameter(
     *         name="userId",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="success", type="boolean"),
     *             )
     *         )
     *     ),
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $affectedRows = $this->getThemeService()
            ->getThemeDao()
            ->deleteThemeByThemeName(ThemeService::CUSTOM_THEME);
        $this->getThemeService()->resetThemeCache();

        return new EndpointResourceResult(ArrayModel::class, ['success' => !$affectedRows == 0]);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection();
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
    }
}
