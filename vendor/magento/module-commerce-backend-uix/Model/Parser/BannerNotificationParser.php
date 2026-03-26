<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\Parser;

/**
 * Banner notification parser for registration
 */
class BannerNotificationParser implements ParserInterface
{
    private const BANNER_NOTIFICATION = 'bannerNotification';

    /**
     * @inheritdoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        $bannerNotifications = $loadedRegistrations[self::BANNER_NOTIFICATION] ?? [];
        if (!empty($bannerNotifications)) {
            $parsedRegistrations[self::BANNER_NOTIFICATION] =
                array_merge($parsedRegistrations[self::BANNER_NOTIFICATION] ?? [], $bannerNotifications);
        }
    }
}
