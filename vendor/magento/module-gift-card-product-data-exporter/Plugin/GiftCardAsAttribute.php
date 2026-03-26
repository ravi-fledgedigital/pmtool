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

namespace Magento\GiftCardProductDataExporter\Plugin;

use Magento\DataExporter\Export\Processor;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option as GiftCardOption;
use Magento\GiftCardProductDataExporter\Model\Provider\Product\OptionsUid;

/**
 * Plugin responsible for:
 * - Adding gift card options to product feed as "ac_giftcard" attribute
 * - Modifying attribute metadata for "ac_giftcard" attribute
 *
 * For gift card products, add gift card options to product.attributes[code="ac_giftcard"] by extracting data from
 * shopperInputOptions and optionsV2 fields.
 *
 * Intentionally keep logic in plugin to simplify future refactoring: eventually legacy approach for gift card options
 * would be eliminated.
 */
class GiftCardAsAttribute
{
    private const GIFTCARD_ATTRIBUTE_CODE = 'ac_giftcard';

    private const TYPE_DEFAULT = 'string';
    private const TYPE_NUMBER = 'number';
    private const RENDER_TYPE_MAPPING = [
        'text' => self::TYPE_DEFAULT,
        'email' => 'email',
        'giftcard_open_amount' => self::TYPE_NUMBER
    ];

    private const AVAILABLE_OPTIONS = [
        GiftCardOption::KEY_AMOUNT,
        GiftCardOption::KEY_SENDER_NAME,
        GiftCardOption::KEY_SENDER_EMAIL,
        GiftCardOption::KEY_RECIPIENT_NAME,
        GiftCardOption::KEY_RECIPIENT_EMAIL,
        GiftCardOption::KEY_MESSAGE,
        GiftCardOption::KEY_CUSTOM_GIFTCARD_AMOUNT,
    ];

    /**
     * @param SerializerInterface $serializer
     * @param OptionsUid $optionsUidProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly OptionsUid $optionsUidProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Append gift card attribute to product feed when processing products feed.
     *
     * @param Processor $processor
     * @param array $feedItems
     * @param string $feedName
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(Processor $processor, array $feedItems, string $feedName): array
    {
        if ($feedName === 'products') {
            $this->addAttributeToProductFeed($feedItems);
        }
        return $feedItems;
    }

    /**
     * Add ac_giftcard attribute to gift card products in feed.
     *
     * @param array $products
     * @return void
     */
    private function addAttributeToProductFeed(array &$products): void
    {
        foreach ($products as &$product) {
            if ($product['type'] === GiftCard::TYPE_GIFTCARD) {
                if (!isset($product['shopperInputOptions'])) {
                    $this->logger->warning(
                        sprintf('GiftCard %s does not have shopper input options', $product['sku'])
                    );
                    continue;
                }

                // pass as a reference to update shopperInputOptions[].uid field in original payload
                $giftCardAttributeData = $this->buildAttributeData($product);
                if ($giftCardAttributeData) {
                    $product['attributes'][] = [
                        'attributeCode' => self::GIFTCARD_ATTRIBUTE_CODE,
                        'value' => [$giftCardAttributeData],
                    ];
                }
            }
        }
    }

    /**
     * Build serialized attribute data for gift card options.
     *
     * @param array $product
     * @return string|null
     */
    private function buildAttributeData(array &$product): ?string
    {
        $options = $this->getOptionsFromShopperInput($product['shopperInputOptions']);
        if (!$options) {
            $this->logger->warning(
                sprintf('GiftCard %s does\'t have valid options: %s', $product['sku'], $product['shopperInputOptions'])
            );
            return null;
        }
        $fixedAmount = $this->getGiftCardFixedAmount($product['optionsV2'] ?? []);
        if ($fixedAmount) {
            $options[] = $fixedAmount;
        }

        return $this->serializer->serialize([
            'options' => $options
        ]);
    }

    /**
     * Create validation rule array from input option.
     *
     * @param array $inputOption
     * @return array
     */
    private function createValidationRule(array $inputOption): array
    {
        $validationRule = [];
        $validationRule['type'] = isset($inputOption['renderType'])
            ? self::RENDER_TYPE_MAPPING[$inputOption['renderType']] ?? self::TYPE_DEFAULT
            : self::TYPE_DEFAULT;
        // all default fields configured in AC are required
        $validationRule['required'] = true;

        if (!isset($inputOption['range'])) {
            return $validationRule;
        }
        $from = $inputOption['range']['from'] ?? null;
        $to = $inputOption['range']['to'] ?? null;
        if ($validationRule['type'] === self::TYPE_DEFAULT) {
            if ($from !== null) {
                $validationRule['min_length'] = $from;
            }
            if ($to !== null) {
                $validationRule['max_length'] = $to;
            }
        } elseif ($validationRule['type'] === self::TYPE_NUMBER) {
            if ($from !== null) {
                $validationRule['min'] = $from;
            }
            if ($to !== null) {
                $validationRule['max'] = $to;
            }
        }

        return $validationRule;
    }

    /**
     * Extract options from shopperInputOptions and update uids.
     *
     * @param array $shopperInputOptions
     * @return array
     */
    private function getOptionsFromShopperInput(array &$shopperInputOptions): array
    {
        $options = [];
        foreach ($shopperInputOptions as &$inputOption) {
            $optionId = $inputOption['id'];
            if (in_array($optionId, self::AVAILABLE_OPTIONS)) {
                $uid = $this->optionsUidProvider->getShopperInputOptionUid($optionId);
                // update original shopperInputOption for gift card with uid
                $inputOption['id'] = $uid;

                $options[] = [
                    'name' => $this->buildName($optionId),
                    'uid' => $uid,
                    'label' => $inputOption['label'],
                    'validation_rule' => $this->createValidationRule($inputOption),
                ];
            }
        }
        return $options;
    }

    /**
     * Extract fixed amount option from optionsV2 if present.
     *
     * @param array $options
     * @return array
     */
    private function getGiftCardFixedAmount(array $options): array
    {
        $fixedAmount = [];
        foreach ($options as $option) {
            if ($option['renderType'] !== Giftcard::TYPE_GIFTCARD
                && $option['renderType'] !== GiftCardOption::KEY_AMOUNT) {
                continue;
            }
            $amounts = array_map(fn($value) => floatval($value['price'] ?? 0), $option['values'] ?? []);

            $fixedAmount = [
                'name' => $this->buildName(GiftCardOption::KEY_AMOUNT),
                'uid' => $this->optionsUidProvider->getShopperInputOptionUid(GiftCardOption::KEY_AMOUNT),
                'label' => $option['label'] ?? __('Amount'),
                'validation_rule' => [
                    'type' => self::TYPE_NUMBER,
                    'required' => true,
                    'values' => $amounts,
                ]
            ];
        }
        return $fixedAmount;
    }

    /**
     * Build attribute name for gift card option.
     *
     * @param string $optionPrefix
     * @return string
     */
    private function buildName(string $optionPrefix): string
    {
        return Giftcard::TYPE_GIFTCARD . '/' . $optionPrefix;
    }
}
