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

namespace Magento\CommerceBackendUix\Plugin\Model;

use Magento\CommerceBackendUix\Model\Cache\CacheInvalidator;
use Magento\CommerceBackendUix\Model\Config\ConfigPath;
use Magento\Config\Model\Config as BaseConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Plugin class to invalidate cache after saving configuration in Admin UI SDK.
 */
class Config
{
    /**
     * @param CacheInvalidator $cacheInvalidator
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private CacheInvalidator $cacheInvalidator,
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * Invalidate cache after saving specific configuration in Admin UI SDK.
     *
     * @param BaseConfig $subject
     * @param callable $proceed
     * @return callable
     */
    public function aroundSave(BaseConfig $subject, callable $proceed)
    {
        $url = $this->urlBuilder->getCurrentUrl();
        if (!str_contains($url, 'admin_ui_sdk')) {
            return $proceed();
        }

        $oldValues = [
            $subject->getConfigDataValue(ConfigPath::ADMIN_UI_SDK_ENABLED_CONFIG_PATH),
            $subject->getConfigDataValue(ConfigPath::ENABLE_TESTING_CONFIG_PATH),
            $subject->getConfigDataValue(ConfigPath::TESTING_MODE_CONFIG_PATH),
            $subject->getConfigDataValue(ConfigPath::APP_STATUS_CONFIG_PATH),
            $subject->getConfigDataValue(ConfigPath::MOCKED_SERVICE_BASE_URL_CONFIG_PATH)
        ];

        $returnValue = $proceed();

        $newValues = [
            $subject->getData('groups/general_config/fields/enable_admin_ui_sdk/value'),
            $subject->getData('groups/local_testing/fields/enable_testing/value'),
            $subject->getData('groups/local_testing/fields/testing_mode/value'),
            $subject->getData('groups/local_testing/fields/app_status/value'),
            $subject->getData('groups/local_testing/fields/server_base_url/value')
        ];

        if ($oldValues !== $newValues) {
            $this->cacheInvalidator->invalidate();
        }

        return $returnValue;
    }
}
