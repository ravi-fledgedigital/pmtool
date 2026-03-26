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

namespace Magento\CommerceBackendUix\Model;

use Magento\CommerceBackendUix\Model\Cache\Cache;

/**
 * Filter banner notifications by mass action id
 */
class BannerNotificationFilter
{
    /**
     * @param Cache $cache
     */
    public function __construct(
        private Cache $cache
    ) {
    }

    /**
     * Get and filter cached banner notification by mass action id
     *
     * @param string $gridType
     * @param string $massActionId
     * @return array
     */
    public function getMassActionBannerNotification(string $gridType, string $massActionId): array
    {
        $bannerNotifications = $this->cache->getBannerNotifications()['massActions'] ?? [];
        $gridNotifications = $bannerNotifications[$gridType] ?? [];
        if (!is_array($gridNotifications)) {
            return [];
        }
        foreach ($gridNotifications as $notification) {
            if (isset($notification['actionId']) && $notification['actionId'] === $massActionId) {
                return $notification;
            }
        }
        return [];
    }

    /**
     * Get and filter cached banner notification by order view button id
     *
     * @param string $orderViewButtonId
     * @return array
     */
    public function getOrderViewButtonBannerNotification(string $orderViewButtonId): array
    {
        $bannerNotifications = $this->cache->getBannerNotifications()['orderViewButtons'] ?? [];
        foreach ($bannerNotifications as $notification) {
            if (isset($notification['buttonId'])
                && $notification['buttonId'] === $orderViewButtonId
            ) {
                return $notification;
            }
        }
        return [];
    }
}
