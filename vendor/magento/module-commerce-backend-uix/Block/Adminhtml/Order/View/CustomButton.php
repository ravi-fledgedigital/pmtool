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

namespace Magento\CommerceBackendUix\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Button;
use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\ConfigInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Framework\UrlInterface;

/**
 * Adminhtml sales order custom buttons
 *
 * @api
 */
class CustomButton extends View
{
    private const LABEL = 'label';
    private const ON_CLICK = 'onclick';
    private const BUTTON_ID = 'buttonId';
    private const LEVEL = 'level';
    private const SORT_ORDER = 'sortOrder';
    private const EXTENSION_ID = 'extensionId';
    private const CONFIRM = 'confirm';
    private const MESSAGE = 'message';
    private const CONTROLLER_URL = 'adminuisdk/order/button';
    private const ORDER_ID = 'orderId';
    private const DEFAULT_SORT_ORDER = 0;
    private const DEFAULT_LEVEL = 0;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ConfigInterface $salesConfig
     * @param Reorder $reorderHelper
     * @param UrlInterface $urlBuilder
     * @param Cache $cache
     * @param AuthorizationValidator $authorization
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private Registry $registry,
        private ConfigInterface $salesConfig,
        private Reorder $reorderHelper,
        private UrlInterface $urlBuilder,
        private Cache $cache,
        private AuthorizationValidator $authorization,
        private array $data = []
    ) {
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

    /**
     * Add custom buttons
     *
     * @return $this
     */
    public function addButtons(): CustomButton
    {
        if (!$this->authorization->isAuthorized()) {
            return $this;
        }

        if ($parentBlock = $this->getParentBlock()) {
            $registeredOrderViewButtons = $this->cache->getOrderViewButtons();
            foreach ($registeredOrderViewButtons as $button) {
                $location = $this->urlBuilder->getUrl(
                    self::CONTROLLER_URL,
                    [
                        self::BUTTON_ID => $button[self::BUTTON_ID],
                        self::EXTENSION_ID => $button[self::EXTENSION_ID],
                        self::ORDER_ID => $parentBlock->getOrderId()
                    ]
                );

                $this->getToolbar()->addChild(
                    $button[self::BUTTON_ID],
                    Button::class,
                    [
                        self::LABEL => __($button[self::LABEL]),
                        self::ON_CLICK => empty($button[self::CONFIRM])
                            ? "setLocation('{$location}')"
                            : "confirmSetLocation('{$button[self::CONFIRM][self::MESSAGE]}', '{$location}')",
                        self::LEVEL => $button[self::LEVEL] ?? self::DEFAULT_LEVEL,
                        self::SORT_ORDER => $button[self::SORT_ORDER] ?? self::DEFAULT_SORT_ORDER
                    ]
                );
            }
        }

        return $this;
    }
}
