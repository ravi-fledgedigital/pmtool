<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookInfo;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;

/**
 * @inheritDoc
 */
class WebhooksPredefinedInfo implements PredefinedWebhookInfoInterface
{
    public const TYPE_CLASS = 'class';

    /**
     * @param ClassToArrayConverterInterface $classToArrayConverter
     */
    public function __construct(
        private readonly ClassToArrayConverterInterface $classToArrayConverter
    ) {
    }

    /**
     * Returns webhook info based on the set configuration. Returns null if configuration for the webhook is not set.
     *
     * @param Webhook $webhook
     * @return array[]|null
     */
    public function get(Webhook $webhook): ?array
    {
        $config = $this->getConfig($webhook->getName());
        if (empty($config)) {
            return null;
        }

        if (str_starts_with($webhook->getName(), Webhook::WEBHOOK_OBSERVER) && isset($config['data'])) {
            $config['data'] = $this->transformConfig($config['data']);
        }

        if ($webhook->getType() === Webhook::TYPE_AFTER) {
            $config['result'] = 'mixed';
        }

        return $config;
    }

    /**
     * Returns configuration for the webhooks payload information.
     *
     * @param string $webhookName
     * @return array[]
     */
    public function getConfig(string $webhookName): array
    {
        $config = [
            'observer.sales_quote_add_item' => [
                'eventName' => 'string',
                'data' => [
                    'quoteItem' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Item::class
                    ]
                ]
            ],
            'observer.checkout_cart_product_add_before' => [
                'eventName' => 'string',
                'data' => [
                    'info' => [
                        'value' => 'mixed'
                    ],
                    'product' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Product::class
                    ]
                ]
            ],
            'observer.sales_order_place_before' => [
                'eventName' => 'string',
                'data' => [
                    'order' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Order::class
                    ]
                ]
            ],
            'observer.sales_quote_merge_after' => [
                'eventName' => 'string',
                'data' => [
                    'quote' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Quote::class
                    ],
                    'source' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Quote::class
                    ]
                ]
            ],
            'observer.sales_order_view_custom_attributes_update_before' => [
                'eventName' => 'string',
                'data' => [
                    'custom_attributes' => [
                        'value' => 'object{}'
                    ],
                    'order' => [
                        'type' => self::TYPE_CLASS,
                        'value' => Order::class
                    ],
                ]
            ],
        ];

        return $config[$webhookName] ?? [];
    }

    /**
     * Transform webhooks payload configuration into a webhooks info array.
     *
     * @param array $config
     * @return array
     */
    private function transformConfig(array $config): array
    {
        foreach ($config as $key => $valueConfig) {
            if ($valueConfig instanceof DataObject) {
                $config[$key] = $this->transformConfig($valueConfig->getData());
                continue;
            }
            if (isset($valueConfig['type']) && $valueConfig['type'] === self::TYPE_CLASS) {
                $config[$key] = $this->classToArrayConverter->convert($valueConfig['value'], 2);
            } else {
                $config[$key] = $valueConfig['value'] ?? $valueConfig;
            }
        }
        return $config;
    }
}
