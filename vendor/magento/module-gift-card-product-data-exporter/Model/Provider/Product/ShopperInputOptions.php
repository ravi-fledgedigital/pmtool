<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2022 Adobe
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

namespace Magento\GiftCardProductDataExporter\Model\Provider\Product;

use Exception;
use Magento\CatalogDataExporter\Model\Provider\EavAttributes\EavAttributesProvider;
use Magento\CatalogDataExporter\Model\Provider\Product\ProductShopperInputOptionProviderInterface;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GiftCard\Model\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option as GiftCardOption;
use Magento\Store\Model\ScopeInterface;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use function array_merge;

/**
 * Gift card product shopper input options data provider, used for GraphQL resolver processing.
 * @deprecared Current approach with shopper input options will be removed. Product will be stored within attribute data
 * @see \Magento\GiftCardProductDataExporter\Plugin\GiftCardAsAttribute
 */
class ShopperInputOptions implements ProductShopperInputOptionProviderInterface
{
    /**
     * @var OptionsUid
     */
    private $optionsUidProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EavAttributesProvider
     */
    private $eavAttributesProvider;

    /**
     * @var array
     */
    private $shopperInputOptions;

    /**
     * Stores storeView scoped configuration for gift card message length
     *
     * @var array
     */
    private $messageLengthInStore;

    /**
     * Stores storeView scoped configuration for whether gift card message is available
     *
     * @var array
     */
    private $messageAvailableInStore;

    /**
     * @param OptionsUid $optionsUidProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param EavAttributesProvider $eavAttributesProvider
     * @param array $shopperInputOptions
     */
    public function __construct(
        OptionsUid $optionsUidProvider,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EavAttributesProvider $eavAttributesProvider,
        array $shopperInputOptions = []
    ) {
        $this->optionsUidProvider = $optionsUidProvider;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->eavAttributesProvider = $eavAttributesProvider;
        $this->shopperInputOptions = $shopperInputOptions;
    }

    /**
     * @inheritDoc
     */
    public function get(array $values): array
    {
        $queryArguments = [];

        foreach ($values as $value) {
            $queryArguments[$value['storeViewCode']][$value['productId']] = $value['productId'];
        }

        try {
            $output = [];
            $attributesData = $this->getAttributesData($queryArguments);
            foreach ($attributesData as $storeViewCode => $productData) {
                foreach ($productData as $productId => $attributes) {
                    $output[] = $this->generateShopperInputOptions((string)$productId, $storeViewCode, $attributes);
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve gift product shopper input options data');
        }
        return $output ? array_merge(...$output) : [];
    }

    /**
     * Get attribute values per gift card product and store view to determine what input options are relevant.
     *
     * @param array $queryArguments
     * @return array
     * @throws UnableRetrieveData
     */
    private function getAttributesData(array $queryArguments): array
    {
        $attributesData = [];
        foreach ($queryArguments as $storeViewCode => $productIds) {
            $attributesData[$storeViewCode] = $this->eavAttributesProvider->getEavAttributesData(
                $productIds,
                $storeViewCode
            );
        }
        return $attributesData;
    }

    /**
     * Generate shopper input options for a gift card product per store view
     *
     * @param string $productId
     * @param string $storeViewCode
     * @param array $attributes
     * @return array
     */
    private function generateShopperInputOptions(string $productId, string $storeViewCode, array $attributes): array
    {
        $relevantOptionKeys = $this->getRelevantOptionKeys($attributes, $storeViewCode);
        $output = [];
        foreach ($this->shopperInputOptions as $option) {
            if (!in_array($option['optionKey'], $relevantOptionKeys, true)) {
                continue;
            }

            $optionKey = $option['optionKey'];
            $range = [];
            if ($optionKey === GiftCardOption::KEY_CUSTOM_GIFTCARD_AMOUNT) {
                $range = [
                    'from' => $attributes['openAmountMin'],
                    'to' => $attributes['openAmountMax']
                ];
            }
            if ($optionKey === GiftCardOption::KEY_MESSAGE) {
                $range = [
                    'from' => 0,
                    'to' => $this->getMessageLength($storeViewCode)
                ];
            }

            $key = $this->getOptionOutputKey($productId, $storeViewCode, $optionKey);

            $formattedOption = $this->formatOption(
                $productId,
                $storeViewCode,
                $optionKey,
                $option['label'],
                $option['renderType'],
                $range
            );
            $output[$key] = $formattedOption;
        }

        return $output;
    }

    /**
     * Get the relevant options depending on product and storeView
     *
     * @param array $attributes
     * @param string $storeViewCode
     * @return array
     */
    private function getRelevantOptionKeys(array $attributes, string $storeViewCode): array
    {
        $applicableOptions = [
            GiftCardOption::KEY_RECIPIENT_NAME,
            GiftCardOption::KEY_SENDER_NAME
        ];
        if ((int)$attributes['giftcardType'] === Giftcard::TYPE_VIRTUAL) {
            $applicableOptions[] = GiftCardOption::KEY_RECIPIENT_EMAIL;
            $applicableOptions[] = GiftCardOption::KEY_SENDER_EMAIL;
        }
        if ($this->isMessageAvailable($attributes, $storeViewCode)) {
            $applicableOptions[] = GiftCardOption::KEY_MESSAGE;
        }
        if ((int)$attributes['allowOpenAmount'] === Giftcard::OPEN_AMOUNT_ENABLED) {
            $applicableOptions[] = GiftCardOption::KEY_CUSTOM_GIFTCARD_AMOUNT;
        }
        return $applicableOptions;
    }

    /**
     * Format shopper input option array
     *
     * @param string $productId
     * @param string $storeViewCode
     * @param string $uid
     * @param string $label
     * @param string $renderType
     * @param array $range
     * @return array
     */
    private function formatOption(
        string $productId,
        string $storeViewCode,
        string $uid,
        string $label,
        string $renderType,
        array $range
    ): array {
        $option = [
            'productId' => $productId,
            'storeViewCode' => $storeViewCode,
            'shopperInputOptions' => [
                'label' => $label,
                'renderType' => $renderType,
                // passed just option key, real uid will be generated in GiftCardAsAttribute
                'id' => $uid
            ]
        ];
        if (!empty($range)) {
            $option['shopperInputOptions']['range'] = $range;
        }
        return $option;
    }

    /**
     * Generate option output key
     *
     * @param string $productId
     * @param string $storeViewCode
     * @param string $optionKey
     * @return string
     */
    private function getOptionOutputKey(string $productId, string $storeViewCode, string $optionKey): string
    {
        return $productId . $storeViewCode . $optionKey;
    }

    /**
     * Is Gift Card Message available for the store
     *
     * @param array $attributes
     * @param string $storeViewCode
     * @return bool
     */
    private function isMessageAvailable(array $attributes, string $storeViewCode): bool
    {
        if ((int)$attributes['useConfigAllowMessage']) {
            if (empty($this->messageAvailableInStore[$storeViewCode])) {
                $this->messageAvailableInStore[$storeViewCode] =
                    $this->scopeConfig->isSetFlag(
                        Giftcard::XML_PATH_ALLOW_MESSAGE,
                        ScopeInterface::SCOPE_STORE,
                        $storeViewCode
                    );
            }
            return $this->messageAvailableInStore[$storeViewCode];
        }
        return (bool)(int)$attributes['allowMessage'];
    }

    /**
     * Get Gift Card Message length in store
     *
     * @param string $storeViewCode
     * @return string
     */
    public function getMessageLength(string $storeViewCode): string
    {
        if (empty($this->messageLengthInStore[$storeViewCode])) {
            $this->messageLengthInStore[$storeViewCode] =
                $this->scopeConfig->getValue(
                    Giftcard::XML_PATH_MESSAGE_MAX_LENGTH,
                    ScopeInterface::SCOPE_STORE,
                    $storeViewCode
                );
        }
        return $this->messageLengthInStore[$storeViewCode];
    }
}
