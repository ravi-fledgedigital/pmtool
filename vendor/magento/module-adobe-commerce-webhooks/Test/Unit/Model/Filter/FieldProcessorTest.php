<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Filter;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\Filter\Converter\HookFieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Filter\FieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Filter\FieldFilter;
use Magento\AdobeCommerceWebhooks\Model\Filter\FieldProcessor;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see FieldProcessor
 */
class FieldProcessorTest extends TestCase
{
    /**
     * @var FieldProcessor
     */
    private FieldProcessor $fieldProcessor;

    /**
     * @var ContextPool|MockObject
     */
    private ContextPool|MockObject $contextPoolMock;

    /**
     * @var ContextRetriever|MockObject
     */
    private ContextRetriever|MockObject $contextRetrieverMock;

    /**
     * @var HookFieldConverter|MockObject
     */
    private HookFieldConverter|MockObject $hookFieldConverterMock;

    protected function setUp(): void
    {
        $this->contextPoolMock = $this->createMock(ContextPool::class);
        $this->contextRetrieverMock = $this->createMock(ContextRetriever::class);
        $this->hookFieldConverterMock = $this->createMock(HookFieldConverter::class);
        $this->fieldProcessor = new FieldProcessor(
            $this->contextPoolMock,
            $this->contextRetrieverMock,
            new FieldConverter(),
            new FieldFilter($this->hookFieldConverterMock),
            $this->hookFieldConverterMock
        );
    }

    /**
     * @param array $data
     * @param array $hookFields
     * @param array $expectedData
     * @return void
     */
    #[DataProvider('processProvider')]
    public function testProcess(array $data, array $hookFields, array $expectedData)
    {
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');
        $this->hookFieldConverterMock->expects(self::never())
            ->method('convertToExternalFormat');

        self::assertEquals(
            $expectedData,
            $this->fieldProcessor->process($data, $hookFields)
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function processProvider(): array
    {
        return [
            'simple hook fields' => [
                'data' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct',
                            'category' => [
                                'name' => 'testCategory',
                                'id' => 'testId'
                            ],
                            'cost' => 5
                        ]
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'data.product.cost']),
                    new HookField(['name' => 'data.product.category.name'])
                ],
                'expectedData' => [
                    'data' => [
                        'product' => [
                            'category' => [
                                'name' => 'testCategory'
                            ],
                            'cost' => 5
                        ]
                    ]
                ]
            ],
            'hook fields with sources' => [
                'data' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct',
                            'category' => [
                                'name' => 'testCategory',
                                'id' => 'testId'
                            ],
                            'cost' => 5,
                        ],
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'product_name', 'source' => 'data.product.name']),
                    new HookField(['name' => 'category_name', 'source' => 'data.product.category.name'])
                ],
                'expectedData' => [
                    'product_name' => 'testProduct',
                    'category_name' => 'testCategory'
                ]
            ],
            'hook fields with common source paths' => [
                'data' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct',
                            'sku' => '00012',
                            'cost' => 5,
                            'id' => 8
                        ],
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'product.name', 'source' => 'data.product.name']),
                    new HookField(['name' => 'product.info.sku', 'source' => 'data.product.sku'])
                ],
                'expectedData' => [
                    'product' => [
                        'name' => 'testProduct',
                        'info' => [
                            'sku' => '00012'
                        ]
                    ]
                ]
            ],
            'array hook field with source' => [
                'data' => [
                    'data' => [
                        'order' => [
                            'items' => [
                                ['name' => 'item1'],
                                ['name' => 'item2']
                            ],
                            'addresses' => [],
                            'base_grand_total' => 20
                        ]
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'purchased', 'source' => 'data.order.items']),
                ],
                'expectedData' => [
                    'purchased' => [
                        ['name' => 'item1'],
                        ['name' => 'item2']
                    ]
                ]
            ],
            'array nested hook fields' => [
                'data' => [
                    'data' => [
                        'order' => [
                            'items' => [
                                [
                                    'name' => 'item 1',
                                    'base_price' => 5,
                                    'description' => 'description 1'
                                ],
                                [
                                    'name' => 'item 2',
                                    'base_price' => 2.75,
                                    'description' => 'description 2'
                                ]
                            ],
                            'customer_firstname' => 'testFirstName',
                            'customer_lastname' => 'testLastName',
                            'customer_email' => 'test@test.com'
                        ]
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'data.order.items[].name']),
                    new HookField(['name' => 'data.order.items[].description']),
                    new HookField(['name' => 'email', 'source' => 'data.order.customer_email'])
                ],
                'expectedData' => [
                    'data' => [
                        'order' => [
                            'items' => [
                                [
                                    'name' => 'item 1',
                                    'description' => 'description 1',
                                ],
                                [
                                    'name' => 'item 2',
                                    'description' => 'description 2',
                                ]
                            ],
                        ]
                    ],
                    'email' => 'test@test.com'
                ]
            ],
            'some nonexistent fields' => [
                'data' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct',
                            'cost' => 5
                        ]
                    ]
                ] ,
                'hookFields' => [
                    new HookField(['name' => 'data.product.name']),
                    new HookField(['name' => 'cost', 'source' => 'product.cost']),
                    new HookField(['name' => 'descriptions', 'source' => 'data.order.items[].description']),
                ],
                'expectedData' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct'
                        ]
                    ],
                    'cost' => null,
                    'descriptions' => []
                ]
            ],
            'array nested hook fields with keys' => [
                'data' => [
                    'data' => [
                        'order' => [
                            'items' => [
                                '33' => [
                                    'name' => 'item 1',
                                    'base_price' => 5,
                                    'description' => 'description 1'
                                ],
                                '32' => [
                                    'name' => 'item 2',
                                    'base_price' => 2.75,
                                    'description' => 'description 2'
                                ]
                            ],
                        ]
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'data.order.items[]']),
                ],
                'expectedData' => [
                    'data' => [
                        'order' => [
                            'items' => [
                                [
                                    'name' => 'item 1',
                                    'base_price' => 5,
                                    'description' => 'description 1',
                                ],
                                [
                                    'name' => 'item 2',
                                    'description' => 'description 2',
                                    'base_price' => 2.75,
                                ]
                            ],
                        ]
                    ],
                ]
            ],
            'some non-existent fields without a source and a parent key not found in the data' => [
                'data' => [
                    'info' => [
                        'product' => [
                            'name' => 'testProduct'
                        ]
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'info.product.name']),
                    new HookField(['name' => 'product.info.cost']),
                    new HookField(['name' => 'product.info.sku'])
                ],
                'expectedData' => [
                    'info' => [
                        'product' => [
                            'name' => 'testProduct'
                        ]
                    ],
                    'product' => [
                        'info' => [
                            'cost' => null,
                            'sku' => null
                        ]
                    ]
                ]
            ],
            'non-existent field with a parent key having a scalar value' => [
                'data' => [
                    'product' => [
                        'sku' => 'test_sku'
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'key1.key2', 'source' => 'product.sku.key'])
                ],
                'expectedData' => [
                    'key1' => [
                        'key2' => null
                    ]
                ]
            ],
            'hook fields with some removed' => [
                'data' => [
                    'data' => [
                        'product' => [
                            'name' => 'testProduct',
                            'category' => [
                                'name' => 'testCategory',
                                'id' => 'testId'
                            ],
                            'cost' => 5,
                        ],
                    ]
                ],
                'hookFields' => [
                    new HookField(['name' => 'product_cost', 'source' => 'data.product.cost', 'remove' => 'true']),
                    new HookField(['name' => 'product_name', 'source' => 'data.product.name', 'remove' => 'false']),
                    new HookField(['name' => 'data.product.category.id', 'remove' => 'true']),
                    new HookField(['name' => 'category_name', 'source' => 'data.product.category.name'])
                ],
                'expectedData' => [
                    'product_name' => 'testProduct',
                    'category_name' => 'testCategory'
                ]
            ],
        ];
    }

    public function testProcessWithContextAccess()
    {
        $inputData = [
            'data' => [
                'product' => [
                    'name' => 'testProduct',
                    'cost' => 5
                ]
            ]
        ];

        $hookFields = [
            new HookField([
                'name' => 'customer_email',
                'source' => 'context_customer_session.email',
                'hook' => $this->createMock(Hook::class)
            ]),
            new HookField([
                'name' => 'product_name',
                'source' => 'data.product.name',
                'hook' => $this->createMock(Hook::class)
            ]),
            new HookField([
                'name' => 'context_state.state',
                'source' => '',
                'hook' => $this->createMock(Hook::class)
            ])
        ];

        $this->contextPoolMock->expects(self::exactly(3))
            ->method('has')
            ->willReturnCallback(function (string $contextReference) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('context_customer_session', $contextReference);
                        return true;
                    case 1:
                        self::assertEquals('data', $contextReference);
                        return false;
                    case 2:
                        self::assertEquals('context_state', $contextReference);
                        return true;
                };
                return null;
            });
        $this->contextRetrieverMock->expects(self::exactly(2))
            ->method('getContextValue')
            ->willReturnCallback(function (string $source) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('context_customer_session.email', $source);
                        return 'test@test.com';
                    case 1:
                        self::assertEquals('context_state.state', $source);
                        return 'test_state';
                };
                return null;
            });
        $this->hookFieldConverterMock->expects(self::never())
            ->method('convertToExternalFormat');

        $expectedData = [
            'customer_email' => 'test@test.com',
            'product_name' => 'testProduct',
            'context_state' => [
                'state' => 'test_state'
            ]
        ];

        self::assertEquals(
            $expectedData,
            $this->fieldProcessor->process($inputData, $hookFields)
        );
    }

    public function testProcessWithConverters()
    {
        $inputData = [
            'data' => [
                'order' => [
                    'items' => [
                        [
                            'name' => 'item 1',
                            'base_price' => 5,
                            'description' => 'description 1'
                        ],
                        [
                            'name' => 'item 2',
                            'base_price' => 2.75,
                            'description' => 'description 2'
                        ]
                    ],
                    'customer_firstname' => 'testFirstName',
                    'order_id' => 4,
                    'customer_email' => 'test@test.com'
                ]
            ]
        ];

        $hookFields = [
            new HookField(['name' => 'data.order.items[].name', 'converter' => 'ItemNameConverter']),
            new HookField(['name' => 'data.order.items[].base_price']),
            new HookField(['name' => 'email', 'source' => 'data.order.customer_email']),
            new HookField(['name' => 'order_id', 'source' => 'data.order.order_id', 'converter' => 'OrderIdConverter'])
        ];

        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');
        $this->hookFieldConverterMock->expects(self::exactly(3))
            ->method('convertToExternalFormat')
            ->willReturnCallback(
                function (mixed $value, HookField $field, array $pluginData) use ($hookFields, $inputData) {
                    static $count = 0;
                    self::assertEquals($pluginData, $inputData);
                    switch ($count++) {
                        case 0:
                            self::assertEquals('item 1', $value);
                            self::assertEquals($hookFields[0], $field);
                            return 'converted name 1';
                        case 1:
                            self::assertEquals('item 2', $value);
                            self::assertEquals($hookFields[0], $field);
                            return 'converted name 2';
                        case 2:
                            self::assertEquals(4, $value);
                            self::assertEquals($hookFields[3], $field);
                            return '1004';
                    };
                    return null;
                }
            );

        $expectedData = [
            'data' => [
                'order' => [
                    'items' => [
                        [
                            'name' => 'converted name 1',
                            'base_price' => 5,
                        ],
                        [
                            'name' => 'converted name 2',
                            'base_price' => 2.75,
                        ]
                    ],
                ]
            ],
            'order_id' => 1004,
            'email' => 'test@test.com'
        ];

        self::assertEquals(
            $expectedData,
            $this->fieldProcessor->process($inputData, $hookFields)
        );
    }
}
