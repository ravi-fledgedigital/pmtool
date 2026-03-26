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

namespace Magento\CommerceBackendUix\Ui\Component;

use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\UrlInterface;

/**
 * Class to centralize mass actions for Apps
 */
class MassAction
{
    private const LABEL = 'label';
    private const TYPE = 'type';
    private const CONFIRM = 'confirm';
    private const URL = 'url';
    private const PRODUCT_CONTROLLER_URL = 'adminuisdk/product/massAction';
    private const SALES_ORDER_CONTROLLER_URL = 'adminuisdk/order/massAction';
    private const CUSTOME_CONTROLLER_URL = 'adminuisdk/customer/massAction';
    private const ACTION_ID = 'actionId';
    private const PRODUCT_ACTION_ID = 'productActionId';
    private const ORDER_ACTION_ID = 'orderActionId';
    private const CUSTOMER_ACTION_ID = 'customerActionId';
    private const EXTENSION_ID = 'extensionId';

    /**
     * @param UrlInterface $urlBuilder
     * @param Cache $cache
     */
    public function __construct(
        private UrlInterface $urlBuilder,
        private Cache $cache
    ) {
    }

    /**
     * Returns registered mass actions config
     *
     * @param string $gridType
     * @return array
     */
    public function getMassActionsConfig(string $gridType): array
    {
        $massActions = $this->cache->getMassActions($gridType);
        $massActionsConfig = [];
        foreach ($massActions as $massAction) {
            $actionConfig = [
                self::LABEL => $massAction[self::LABEL],
                self::TYPE => $massAction[self::ACTION_ID],
                self::URL => $this->urlBuilder->getUrl(
                    $this->getControllerUrl($gridType),
                    [$this->getActionId($gridType) => $massAction[self::ACTION_ID],
                     self::EXTENSION_ID => $massAction[self::EXTENSION_ID]]
                )
            ];
            if (!empty($massAction[self::CONFIRM])) {
                $actionConfig[self::CONFIRM] = $massAction[self::CONFIRM] ?? [];
            }
            $massActionsConfig[] = $actionConfig;
        }
        return $massActionsConfig;
    }

    /**
     * Returns the Action ID param label based on the grid type
     *
     * @param string $gridType
     * @return string
     */
    private function getActionId(string $gridType): string
    {
        return match ($gridType) {
            UiGridType::PRODUCT_LISTING_GRID => self::PRODUCT_ACTION_ID,
            UiGridType::SALES_ORDER_GRID => self::ORDER_ACTION_ID,
            UiGridType::CUSTOMER_GRID => self::CUSTOMER_ACTION_ID,
            default => self::ACTION_ID,
        };
    }

    /**
     * Return the controller based on the grid type
     *
     * @param string $gridType
     * @return string
     */
    private function getControllerUrl(string $gridType): string
    {
        return match ($gridType) {
            UiGridType::PRODUCT_LISTING_GRID => self::PRODUCT_CONTROLLER_URL,
            UiGridType::SALES_ORDER_GRID => self::SALES_ORDER_CONTROLLER_URL,
            UiGridType::CUSTOMER_GRID => self::CUSTOME_CONTROLLER_URL,
            default => '',
        };
    }
}
