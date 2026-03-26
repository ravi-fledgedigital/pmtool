<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
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

namespace Magento\CommerceBackendUix\Model\Cache;

use Magento\CommerceBackendUix\Model\Cache\Type\CacheType;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Admin UI SDK cache
 */
class Cache
{
    private const CACHE_LIFETIME = 604800; //1 week
    private const EXTENSION_REGISTERED_IDENTIFIER = '-extension-registered';
    private const REGISTRATION_DATA_IDENTIFIER = '-registration-data';
    private const REGISTRATION_IDENTIFIER = '-registrations';
    private const BANNER_NOTIFICATION = 'bannerNotification';
    private const MASS_ACTIONS = 'massActions';
    private const GRID_COLUMNS = 'gridColumns';
    private const VIEW_BUTTONS = 'viewButtons';
    private const ORDER = 'order';
    private const PRODUCT = 'product';
    private const CUSTOMER = 'customer';
    private const BUTTON_ID = 'buttonId';
    private const ACTION_ID = 'actionId';

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Config $config
     */
    public function __construct(
        private CacheInterface $cache,
        private SerializerInterface $serializer,
        private Config $config
    ) {
    }

    /**
     * Get the registration data when cached
     *
     * @return array
     */
    public function getRegistrationData(): array
    {
        return $this->getData(self::REGISTRATION_DATA_IDENTIFIER);
    }

    /**
     * Set data to cache
     *
     * @param string $data
     * @return void
     */
    public function setRegistrationData(string $data): void
    {
        $cacheData = $this->getRegistrationData();
        if (!in_array($data, $cacheData)) {
            $cacheData[] = $data;
        }
        $this->save($cacheData, self::REGISTRATION_DATA_IDENTIFIER);
    }

    /**
     * Get mass actions by grid type
     *
     * @param string $gridType
     * @return array
     */
    public function getMassActions(string $gridType): array
    {
        $registrations = $this->getRegistrations();
        return match ($gridType) {
            UiGridType::SALES_ORDER_GRID => $registrations[self::ORDER][self::MASS_ACTIONS] ?? [],
            UiGridType::PRODUCT_LISTING_GRID => $registrations[self::PRODUCT][self::MASS_ACTIONS] ?? [],
            UiGridType::CUSTOMER_GRID => $registrations[self::CUSTOMER][self::MASS_ACTIONS] ?? [],
            default => []
        };
    }

    /**
     * Get mass action by actionId
     *
     * @param string $gridType
     * @param string $actionId
     * @return mixed|null
     */
    public function getMassAction(string $gridType, string $actionId): mixed
    {
        foreach ($this->getMassActions($gridType) as $massAction) {
            if (isset($massAction[self::ACTION_ID]) && $massAction[self::ACTION_ID] === $actionId) {
                return $massAction;
            }
        }
        return null;
    }

    /**
     * Get cached extension url by its extension id
     *
     * @param string $extensionId
     * @return string|null
     */
    public function getExtensionUrlByExtensionId(string $extensionId): ?string
    {
        if (empty($extensionId)) {
            return null;
        }

        $extensions = $this->getRegisteredExtensions();

        return $extensions[$extensionId] ?? null;
    }

    /**
     * Get cached extensions
     *
     * @return array
     */
    public function getRegisteredExtensions(): array
    {
        $extensionsByOrgId = $this->getData(self::EXTENSION_REGISTERED_IDENTIFIER);
        $orgId = $this->config->getOrganizationId();
        return (isset($extensionsByOrgId[$orgId]) && is_array($extensionsByOrgId[$orgId]))
            ? $extensionsByOrgId[$orgId]
            : [];
    }

    /**
     * Set registered extensions
     *
     * @param array $extensions
     * @return void
     */
    public function setRegisteredExtensions(array $extensions): void
    {
        $orgId = $this->config->getOrganizationId();
        $extensionsByOrgId = [$orgId => $extensions];
        $this->save($extensionsByOrgId, self::EXTENSION_REGISTERED_IDENTIFIER);
    }

    /**
     * Set registrations
     *
     * @param array $registrations
     * @return void
     */
    public function setRegistrations(array $registrations): void
    {
        $orgId = $this->config->getOrganizationId();
        $registrationsByOrgId = [$orgId => $registrations];
        $this->save($registrationsByOrgId, self::REGISTRATION_IDENTIFIER);
    }

    /**
     * Get registrations
     *
     * @return array
     */
    public function getRegistrations(): array
    {
        $registrationsByOrgId = $this->getData(self::REGISTRATION_IDENTIFIER);
        $orgId = $this->config->getOrganizationId();
        return (isset($registrationsByOrgId[$orgId]) && is_array($registrationsByOrgId[$orgId]))
            ? $registrationsByOrgId[$orgId]
            : [];
    }

    /**
     * Get registered columns
     *
     * @param string $gridType
     * @return array
     */
    public function getRegisteredColumns(string $gridType): array
    {
        $registrations = $this->getRegistrations();
        return match ($gridType) {
            UiGridType::SALES_ORDER_GRID => $registrations[self::ORDER][self::GRID_COLUMNS] ?? [],
            UiGridType::PRODUCT_LISTING_GRID => $registrations[self::PRODUCT][self::GRID_COLUMNS] ?? [],
            UiGridType::CUSTOMER_GRID => $registrations[self::CUSTOMER][self::GRID_COLUMNS] ?? [],
            default => []
        };
    }

    /**
     * Get registered order view buttons
     *
     * @return array
     */
    public function getOrderViewButtons(): array
    {
        $registrations = $this->getRegistrations();
        return $registrations[self::ORDER][self::VIEW_BUTTONS] ?? [];
    }

    /**
     * Get order view button by buttonId
     *
     * @param string $buttonId
     * @return array|null
     */
    public function getOrderViewButton(string $buttonId): ?array
    {
        foreach ($this->getOrderViewButtons() as $viewButton) {
            if (isset($viewButton[self::BUTTON_ID]) && $viewButton[self::BUTTON_ID] === $buttonId) {
                return $viewButton;
            }
        }
        return null;
    }

    /**
     * Save data to cache by identifier
     *
     * @param array $data
     * @param string $identifier
     * @return void
     */
    public function save(array $data, string $identifier): void
    {
        $this->cache->save(
            $this->serializer->serialize($data),
            CacheType::TYPE_IDENTIFIER . $identifier,
            [CacheType::CACHE_TAG],
            self::CACHE_LIFETIME
        );
    }

    /**
     * Get registered banner notification
     *
     * @return array
     */
    public function getBannerNotifications(): array
    {
        $registrations = $this->getRegistrations();
        return $registrations[self::BANNER_NOTIFICATION] ?? [];
    }

    /**
     * Get data from cache by identifier
     *
     * @param string $identifier
     * @return array
     */
    public function getData(string $identifier): array
    {
        $cacheData = $this->cache->load(CacheType::TYPE_IDENTIFIER . $identifier);
        if ($cacheData) {
            $deserializedData = $this->serializer->unserialize($cacheData);
            return is_array($deserializedData) ? $deserializedData : [];
        }
        return [];
    }
}
