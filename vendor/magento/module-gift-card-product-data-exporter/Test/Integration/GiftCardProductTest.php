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

namespace Magento\GiftCardProductDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;
use function base64_encode;
use function usort;

/**
 * Test for gift card product export
 */
class GiftCardProductTest extends AbstractProductTestHelper
{
    private ArrayUtils $arrayUtils;

    /**
     * @param $attributes
     * @return array
     */
    public function getGiftcardAsAttribute($attributes): array
    {
        $attribute = array_filter($attributes, fn($attribute) => $attribute['attributeCode'] === 'ac_giftcard');
        return $attribute ? array_shift($attribute) : [];
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->arrayUtils = Bootstrap::getObjectManager()->create(ArrayUtils::class);

        parent::setUp();
    }

    /**
     * Validate physical gift card data with fixed amount
     *
     * @param array $item
     * @return void
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @dataProvider getPhysicalFixedAmountOptionsDataProvider
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testGiftCardPhysicalFixedAmountOptions(array $item): void
    {
        $extractedProduct = $this->getExtractedProduct('gift-card-with-fixed-amount-10', 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        $diff = $this->arrayUtils->recursiveDiff($item['feedData'], $extractedProduct['feedData']);
        self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        $actualAttribute = $this->getGiftcardAsAttribute($extractedProduct['feedData']['attributes']);
        self::assertEquals(
            $item['attribute'],
            $actualAttribute,
            'Actual attribute data doesn\'t equal to expected data'
        );
    }

    /**
     * Validate physical gift card data with open amount
     *
     * @param array $item
     * @return void
     * @magentoDataFixture Magento_GiftCardProductDataExporter::Test/Integration/_files/gift_card_without_fixed_amount.php
     * @magentoConfigFixture default_store currency/options/default USD
     * @dataProvider getVirtualOpenAmountOptionsDataProvider
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testGiftCardVirtualOpenAmountOptions(array $item): void
    {
        $extractedProduct = $this->getExtractedProduct('gift-card-without-fixed-amount', 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        $diff = $this->arrayUtils->recursiveDiff($item['feedData'], $extractedProduct['feedData']);
        self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        $actualAttribute = $this->getGiftcardAsAttribute($extractedProduct['feedData']['attributes']);
        self::assertEquals(
            $item['attribute'],
            $actualAttribute,
            'Actual attribute data doesn\'t equal to expected data'
        );
    }

    /**
     * Validate virtual gift card data with fixed amounts in multiple websites
     * @param array $defaultWebsiteItem
     * @param array $secondWebsiteItem
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_with_amount_multiple_websites.php
     * @magentoConfigFixture default_store giftcard/general/allow_message 1
     * @magentoConfigFixture default_store giftcard/general/message_max_length 255
     * @magentoConfigFixture fixture_second_store_store giftcard/general/allow_message 1
     * @magentoConfigFixture fixture_second_store_store giftcard/general/message_max_length 300
     * @dataProvider getVirtualMultiWebsiteDataProvider
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @return void
     */
    public function testGiftCardFixedAmountMultipleWebsites(
        array $defaultWebsiteItem,
        array $secondWebsiteItem
    ): void {
        $extractedProductDefault = $this->getExtractedProduct(
            'gift-card-with-amount',
            'default'
        );

        $this->assertNotEmpty($extractedProductDefault, 'Feed data must not be empty');

        $diff = $this->arrayUtils->recursiveDiff(
            $defaultWebsiteItem['feedData'],
            $this->sortGiftCardProductOptionsValues($extractedProductDefault['feedData'])
        );
        self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');

        $actualAttribute = $this->getGiftcardAsAttribute($extractedProductDefault['feedData']['attributes']);
        self::assertEquals(
            $defaultWebsiteItem['attribute'],
            $actualAttribute,
            'Actual attribute data doesn\'t equal to expected data'
        );

        $extractedProductSecondWebsite = $this->getExtractedProduct(
            'gift-card-with-amount',
            'fixture_second_store'
        );

        $this->assertNotEmpty($extractedProductSecondWebsite, 'Feed data must not be empty');
        $diff = $this->arrayUtils->recursiveDiff(
            $secondWebsiteItem['feedData'],
            $this->sortGiftCardProductOptionsValues($extractedProductSecondWebsite['feedData'])
        );
        self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');

        $actualAttribute = $this->getGiftcardAsAttribute($extractedProductSecondWebsite['feedData']['attributes']);
        self::assertEquals(
            $secondWebsiteItem['attribute'],
            $actualAttribute,
            'Actual attribute data doesn\'t equal to expected data'
        );
    }

    /**
     * Sort gift card product options values for test consistency.
     * Export API doesn't provide sorted product options.
     *
     * @param array $extractedProduct
     *
     * @return array
     */
    private function sortGiftCardProductOptionsValues(array $extractedProduct): array
    {
        foreach ($extractedProduct['optionsV2'] as &$option) {
            usort($option['values'], fn($a, $b) => $a['price'] <=> $b['price']);
        }

        return $extractedProduct;
    }

    /**
     * Physical gift card with fixed amount data provider
     *
     * @return array
     */
    public static function getPhysicalFixedAmountOptionsDataProvider(): array
    {
        return [
            'giftCard' => [
                'item' => [
                    'feedData' => [
                        'sku' => 'gift-card-with-fixed-amount-10',
                        'storeViewCode' => 'default',
                        'name' => 'Gift Card with fixed amount 10',
                        'type' => 'giftcard',
                        'giftcardType' => 'physical',
                        'optionsV2' => [
                            [
                                'label' => 'Amount',
                                'renderType' => 'giftcard_amount',
                                'type' => 'giftcard',
                                'values' => [
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/10.0000'), 'price' => 10]
                                ]
                            ]
                        ],
                        'shopperInputOptions' => [
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_name'),
                                'label' => 'Sender Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_name'),
                                'label' => 'Recipient Name',
                                'renderType' => 'text'
                            ]
                        ]
                    ],
                    'attribute' =>  [
                        'attributeCode' => 'ac_giftcard',
                        'value' => [json_encode([
                            "options" => [
                                [
                                    "name" => "giftcard/giftcard_sender_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_sender_name"),
                                    "label" => "Sender Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_recipient_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_recipient_name"),
                                    "label" => "Recipient Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_amount",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_amount"),
                                    "label" => "Amount",
                                    "validation_rule" => [
                                        "type" => "number",
                                        "required" => true,
                                        "values" => [10]
                                    ]
                                ]
                            ]
                        ])]
                    ]
                ]
            ]
        ];
    }

    /**
     * Physical gift card with fixed amount data provider
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getVirtualOpenAmountOptionsDataProvider(): array
    {
        return [
            'giftCard' => [
                'item' => [
                    'feedData' => [
                        'sku' => 'gift-card-without-fixed-amount',
                        'storeViewCode' => 'default',
                        'name' => 'Simple Gift Card',
                        'type' => 'giftcard',
                        'giftcardType' => 'virtual',
                        'optionsV2' => null,
                        'shopperInputOptions' => [
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_name'),
                                'label' => 'Sender Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_name'),
                                'label' => 'Recipient Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_email'),
                                'label' => 'Sender Email',
                                'renderType' => 'email'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_email'),
                                'label' => 'Recipient Email',
                                'renderType' => 'email'
                            ],
                            [
                                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                'id' => base64_encode('giftcard/custom_giftcard_amount'),
                                'label' => 'Amount in',
                                'renderType' => 'giftcard_open_amount',
                                'range' => [
                                    'from' => 100,
                                    'to' => 1500
                                ]
                            ]
                        ]
                    ],
                    'attribute' =>  [
                        'attributeCode' => 'ac_giftcard',
                        'value' => [json_encode([
                            "options" => [
                                [
                                    "name" => "giftcard/giftcard_sender_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_sender_name"),
                                    "label" => "Sender Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_recipient_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_recipient_name"),
                                    "label" => "Recipient Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_sender_email",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_sender_email"),
                                    "label" => "Sender Email",
                                    "validation_rule" => [
                                        "type" => "email",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_recipient_email",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_recipient_email"),
                                    "label" => "Recipient Email",
                                    "validation_rule" => [
                                        "type" => "email",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/custom_giftcard_amount",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/custom_giftcard_amount"),
                                    "label" => "Amount in",
                                    "validation_rule" => [
                                        "type" => "number",
                                        "required" => true,
                                        "min" => 100,
                                        "max" => 1500
                                    ]
                                ]
                            ]
                        ])]
                    ]
                ]
            ]
        ];
    }

    /**
     * Virtual gift card with fixed and open amount data provider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public static function getVirtualMultiWebsiteDataProvider(): array
    {
        return [
            'giftCards' => [
                'defaultWebsiteItem' => [
                    'feedData' => [
                        'sku' => 'gift-card-with-amount',
                        'storeViewCode' => 'default',
                        'name' => 'Simple Gift Card',
                        'type' => 'giftcard',
                        'giftcardType' => 'virtual',
                        'optionsV2' => [
                            [
                                'label' => 'Amount',
                                'renderType' => 'giftcard_amount',
                                'type' => 'giftcard',
                                'values' => [
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/7.0000'), 'price' => 7],
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/17.0000'), 'price' => 17]
                                ]
                            ]
                        ],
                        'shopperInputOptions' => [
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_name'),
                                'label' => 'Sender Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_name'),
                                'label' => 'Recipient Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_email'),
                                'label' => 'Sender Email',
                                'renderType' => 'email'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_email'),
                                'label' => 'Recipient Email',
                                'renderType' => 'email'
                            ],
                            [
                                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                'id' => base64_encode('giftcard/giftcard_message'),
                                'label' => 'Message',
                                'renderType' => 'text',
                                'range' => [
                                    'from' => 0,
                                    'to' => 255
                                ]
                            ],
                            [
                                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                'id' => base64_encode('giftcard/custom_giftcard_amount'),
                                'label' => 'Amount in',
                                'renderType' => 'giftcard_open_amount',
                                'range' => [
                                    'from' => 100,
                                    'to' => 150
                                ]
                            ]
                        ]
                    ],
                    'attribute' =>  [
                        'attributeCode' => 'ac_giftcard',
                        'value' => [json_encode([
                                "options" => [
                                    [
                                        "name" => "giftcard/giftcard_sender_name",
                                        "uid" => base64_encode("giftcard/giftcard_sender_name"),
                                        "label" => "Sender Name",
                                        "validation_rule" => [
                                            "type" => "string",
                                            "required" => true
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/giftcard_recipient_name",
                                        "uid" => base64_encode("giftcard/giftcard_recipient_name"),
                                        "label" => "Recipient Name",
                                        "validation_rule" => [
                                            "type" => "string",
                                            "required" => true
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/giftcard_sender_email",
                                        "uid" => base64_encode("giftcard/giftcard_sender_email"),
                                        "label" => "Sender Email",
                                        "validation_rule" => [
                                            "type" => "email",
                                            "required" => true
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/giftcard_recipient_email",
                                        "uid" => base64_encode("giftcard/giftcard_recipient_email"),
                                        "label" => "Recipient Email",
                                        "validation_rule" => [
                                            "type" => "email",
                                            "required" => true
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/giftcard_message",
                                        "uid" => base64_encode("giftcard/giftcard_message"),
                                        "label" => "Message",
                                        "validation_rule" => [
                                            "type" => "string",
                                            "required" => true,
                                            "min_length" => 0,
                                            "max_length" => 255
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/custom_giftcard_amount",
                                        "uid" => base64_encode("giftcard/custom_giftcard_amount"),
                                        "label" => "Amount in",
                                        "validation_rule" => [
                                            "type" => "number",
                                            "required" => true,
                                            "min" => 100,
                                            "max" => 150
                                        ]
                                    ],
                                    [
                                        "name" => "giftcard/giftcard_amount",
                                        "uid" => base64_encode("giftcard/giftcard_amount"),
                                        "label" => "Amount",
                                        "validation_rule" => [
                                            "type" => "number",
                                            "required" => true,
                                            "values" => [7, 17]
                                        ]
                                    ]
                                ]
                            ])]
                    ]
                ],
                'secondWebsiteItem' => [
                    'feedData' => [
                        'sku' => 'gift-card-with-amount',
                        'storeViewCode' => 'fixture_second_store',
                        'name' => 'Simple Gift Card',
                        'type' => 'giftcard',
                        'giftcardType' => 'virtual',
                        'optionsV2' => [
                            [
                                'label' => 'Amount',
                                'renderType' => 'giftcard_amount',
                                'type' => 'giftcard',
                                'values' => [
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/7.0000'), 'price' => 7],
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/17.0000'), 'price' => 17],
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/27.0000'), 'price' => 27],
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    ['id' => base64_encode('giftcard/giftcard_amount/37.0000'), 'price' => 37],
                                ]
                            ]
                        ],
                        'shopperInputOptions' => [
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_name'),
                                'label' => 'Sender Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_name'),
                                'label' => 'Recipient Name',
                                'renderType' => 'text'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_sender_email'),
                                'label' => 'Sender Email',
                                'renderType' => 'email'
                            ],
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            [
                                'id' => base64_encode('giftcard/giftcard_recipient_email'),
                                'label' => 'Recipient Email',
                                'renderType' => 'email'
                            ],
                            [
                                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                'id' => base64_encode('giftcard/giftcard_message'),
                                'label' => 'Message',
                                'renderType' => 'text',
                                'range' => [
                                    'from' => 0,
                                    'to' => 300
                                ]
                            ],
                            [
                                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                'id' => base64_encode('giftcard/custom_giftcard_amount'),
                                'label' => 'Amount in',
                                'renderType' => 'giftcard_open_amount',
                                'range' => [
                                    'from' => 100,
                                    'to' => 150
                                ]
                            ]
                        ]
                    ],
                    'attribute' =>  [
                        'attributeCode' => 'ac_giftcard',
                        'value' => [json_encode([
                            "options" => [
                                [
                                    "name" => "giftcard/giftcard_sender_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_sender_name"),
                                    "label" => "Sender Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_recipient_name",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_recipient_name"),
                                    "label" => "Recipient Name",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_sender_email",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_sender_email"),
                                    "label" => "Sender Email",
                                    "validation_rule" => [
                                        "type" => "email",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_recipient_email",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_recipient_email"),
                                    "label" => "Recipient Email",
                                    "validation_rule" => [
                                        "type" => "email",
                                        "required" => true
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_message",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_message"),
                                    "label" => "Message",
                                    "validation_rule" => [
                                        "type" => "string",
                                        "required" => true,
                                        "min_length" => 0,
                                        "max_length" => 300
                                    ]
                                ],
                                [
                                    "name" => "giftcard/custom_giftcard_amount",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/custom_giftcard_amount"),
                                    "label" => "Amount in",
                                    "validation_rule" => [
                                        "type" => "number",
                                        "required" => true,
                                        "min" => 100,
                                        "max" => 150
                                    ]
                                ],
                                [
                                    "name" => "giftcard/giftcard_amount",
                                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                                    "uid" => base64_encode("giftcard/giftcard_amount"),
                                    "label" => "Amount",
                                    "validation_rule" => [
                                        "type" => "number",
                                        "required" => true,
                                        "values" => [7, 17, 27, 37]
                                    ]
                                ]
                            ]
                        ])]
                    ]

                ]
            ]
        ];
    }
}
