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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\WebhookRunner\Response\Operation;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\CaseConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\Add;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\DataUpdater;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\ValueResolver;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\Framework\DataObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see Add class
 */
class AddTest extends TestCase
{
    /**
     * @var Hook|MockObject
     */
    private Hook|MockObject $hookMock;

    /**
     * @var ValueResolver|MockObject
     */
    private ValueResolver|MockObject $valueResolverMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->hookMock = $this->createMock(Hook::class);
        $this->valueResolverMock = $this->createMock(ValueResolver::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    /**
     * @param array $arguments
     * @param array $updatedArguments
     * @param array $configuration
     * @return void
     * @throws OperationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    #[DataProvider('addDataProvider')]
    public function testAdd(array $arguments, array $updatedArguments, array $configuration)
    {
        $addOperation = $this->createAddOperation($configuration);
        $this->valueResolverMock->expects(self::once())
            ->method('resolve')
            ->with($configuration)
            ->willReturn($configuration['value']);
        $this->loggerMock->expects(self::never())
            ->method('debug');
        $this->hookMock->expects(self::never())
            ->method('getName');

        $addOperation->execute($arguments);
        self::assertEquals($updatedArguments, $arguments);
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function addDataProvider(): array
    {
        return [
            'root add' => [
                ['result' => 3],
                [
                    'result' => 3,
                    'result-two' => 4,
                ],
                [
                    'path' => 'result-two',
                    'value' => 4
                ]
            ],
            'nested add' => [
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one'
                        ]
                    ]
                ],
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one',
                            'sku' => 'product-one'
                        ]
                    ]
                ],
                [
                    'path' => 'data/product/sku',
                    'value' => 'product-one'
                ]
            ],
            'nested add array' => [
                [
                    'data' => [
                        'result' => [

                        ]
                    ]
                ],
                [
                    'data' => [
                        'result' => [
                            'test' => [
                                'name' => 'product two',
                                'sku' => 'product-two',
                            ]
                        ]
                    ]
                ],
                [
                    'path' => 'data/result/test',
                    'value' => [
                        'name' => 'product two',
                        'sku' => 'product-two',
                    ]
                ]
            ],
            'nested add to object' => [
                [
                    'data' => [
                        'result' => new DataObject()
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value'])
                    ]
                ],
                [
                    'path' => 'data/result/test_path',
                    'value' => 'value'
                ]
            ],
            'nested add to object array' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'items' => [
                                'item_one' => 'value one'
                            ]
                        ])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([
                            'items' => [
                                'item_one' => 'value one',
                                'item_two' => 'value two',
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/items/item_two',
                    'value' => 'value two'
                ]
            ],
            'nested add a key value pair' => [
                [
                    'data' => [
                        'result' => [
                            'shipping-methods' => [
                                'shipping-one' => 'one'
                            ]
                        ]
                    ]
                ],
                [
                    'data' => [
                        'result' => [
                            'shipping-methods' => [
                                'shipping-one' => 'one',
                                'shipping-two' => 'two',
                            ]
                        ]
                    ]
                ],
                [
                    'path' => 'data/result/shipping-methods/shipping-two',
                    'value' => 'two'
                ]
            ],
            'nested add non an array to array' => [
                [
                    'data' => [
                        'result' => [
                            'shipping-methods' => [
                                'shipping-one' => 'one'
                            ]
                        ]
                    ]
                ],
                [
                    'data' => [
                        'result' => [
                            'shipping-methods' => [
                                'shipping-one' => 'one',
                                'two',
                            ]
                        ]
                    ]
                ],
                [
                    'path' => 'data/result/shipping-methods',
                    'value' => 'two'
                ]
            ],
            'add a single value to the nested object in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value',
                                            'test_path_two' => 'value new',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments/segment_one/test_path_two',
                    'value' => 'value new'
                ]
            ],
            'add an object to the array value of the nested object in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value',
                                        ]),
                                        'segment_two' => new DataObject([
                                            'test_path_two' => 'value',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments/segment_two',
                    'value' => new DataObject([
                        'test_path_two' => 'value',
                    ])
                ]
            ],
            'add an object without the key to the array value of the nested object in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        new DataObject([
                                            'test_path_one' => 'value',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        new DataObject([
                                            'test_path_one' => 'value',
                                        ]),
                                        new DataObject([
                                            'test_path_two' => 'value',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments',
                    'value' => new DataObject([
                        'test_path_two' => 'value',
                    ])
                ]
            ],
        ];
    }

    public function testAddNodeNotExists()
    {
        $addOperation = $this->createAddOperation([
            'path' => 'data/result',
            'value' => [
                'name' => 'product two',
                'sku' => 'product-two',
            ]
        ]);

        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook_add');
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'The webhook operation was unable to add a value by path "data/result" for hook "test_hook_add"' .
                ': The path part "data" does not exist.'
            );
        $this->valueResolverMock->expects(self::never())
            ->method('resolve');
        $arguments = [];
        $addOperation->execute($arguments);
        self::assertEquals([], $arguments);
    }

    public function testAddValueException()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Unable to add the value by path "result/shipping-methods" for hook "test_hook"');

        $addOperation = $this->createAddOperation([
            'path' => 'result/shipping-methods',
            'value' => 'two'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook');
        $this->loggerMock->expects(self::never())
            ->method('debug');
        $this->valueResolverMock->expects(self::once())
            ->method('resolve')
            ->with([
                'path' => 'result/shipping-methods',
                'value' => 'two'
            ])
            ->willReturn('two');

        $arguments = [
            'result' => $addOperation
        ];
        $addOperation->execute($arguments);
        self::assertEquals([], $arguments);
    }

    public function testValueCanNotBeAdded()
    {
        $addOperation = $this->createAddOperation([
                'path' => 'result/test',
                'value' => 'two'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook');
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with('The webhook operation was unable to add a value by path "result/test" for hook "test_hook"');
        $this->valueResolverMock->expects(self::once())
            ->method('resolve')
            ->with([
                'path' => 'result/test',
                'value' => 'two'
            ])
            ->willReturn('two');

        $arguments = [
            'result' => null
        ];
        $addOperation->execute($arguments);
        self::assertEquals(['result' => null], $arguments);
    }

    /**
     * Creates the Add Operation
     *
     * @param array $configuration
     * @return Add
     */
    private function createAddOperation(array $configuration): Add
    {
        return new Add(
            $this->hookMock,
            new CaseConverter(),
            $configuration,
            $this->valueResolverMock,
            new DataUpdater(new CaseConverter()),
            $this->loggerMock
        );
    }
}
