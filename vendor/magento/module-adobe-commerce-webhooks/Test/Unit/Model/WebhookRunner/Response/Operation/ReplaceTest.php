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
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\DataUpdater;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\Replace;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\ValueResolver;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationValueConverter;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see Replace class
 */
class ReplaceTest extends TestCase
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
     * @var OperationValueConverter|MockObject
     */
    private OperationValueConverter|MockObject $valueConverterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->hookMock = $this->createMock(Hook::class);
        $this->valueResolverMock = $this->createMock(ValueResolver::class);
        $this->valueConverterMock = $this->createMock(OperationValueConverter::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    /**
     * @param array $arguments
     * @param array $replacedArguments
     * @param array $configuration
     * @return void
     * @throws OperationException
     * @throws LocalizedException
     */
    #[DataProvider('replaceDataProvider')]
    public function testReplace(array $arguments, array $replacedArguments, array $configuration)
    {
        $replaceOperation = $this->createReplaceOperation($configuration);
        $this->valueResolverMock->expects(self::atMost(1))
            ->method('resolve')
            ->with($configuration)
            ->willReturn($configuration['value']);
        $this->hookMock->expects(self::once())
            ->method('getFields')
            ->willReturn(['field' => 'value']);
        $this->valueConverterMock->expects(self::atMost(1))
            ->method('convert')
            ->with($configuration['value'], $configuration['path'], ['field' => 'value'], $arguments)
            ->willReturn($configuration['value']);
        $this->loggerMock->expects(self::never())
            ->method('debug');

        $replaceOperation->execute($arguments);
        self::assertEquals($replacedArguments, $arguments);
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function replaceDataProvider(): array
    {
        return [
            'replace root' => [
                ['result' => 3],
                ['result' => 4],
                [
                  'path' => 'result',
                  'value' => 4
                ]
            ],
            'replace single value in the nested array structure' => [
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one',
                        ]
                    ]
                ],
                [
                    'data' => [
                        'product' => [
                            'name' => 'product two'
                        ]
                    ]
                ],
                [
                    'path' => 'data/product/name',
                    'value' => 'product two'
                ]
            ],
            'replace array in the nested array structure' => [
                [
                    'data' => [
                        'result' => null
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
                    'path' => 'data/result',
                    'value' => [
                        'test' => [
                            'name' => 'product two',
                            'sku' => 'product-two',
                        ]
                    ]
                ]
            ],
            'replace array partially in the nested array structure' => [
                [
                    'data' => [
                        'result' => [
                            'product' => [
                                'sku' => 'test1',
                                'name' => 'test1',
                                'qty' => 20,
                                'description' => 'product 1',
                                'type' => 'simple'
                            ]
                        ]
                    ]
                ],
                [
                    'data' => [
                        'result' => [
                            'product' => [
                                'sku' => 'test2',
                                'name' => 'test2',
                                'qty' => 20,
                                'description' => 'product 2',
                                'type' => 'simple'
                            ]
                        ]
                    ]
                ],
                [
                    'path' => 'data/result',
                    'value' => [
                        'product' => [
                            'sku' => 'test2',
                            'name' => 'test2',
                            'description' => 'product 2',
                        ]
                    ]
                ]
            ],
            'replace object single value in the nested array structure' => [
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value'])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value new'])
                    ]
                ],
                [
                    'path' => 'data/result/test_path',
                    'value' => 'value new'
                ]
            ],
            'replace nested object value in the nested object structure with a single value' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value',
                                            'test_path_two' => 'value',
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
            'replace nested object with another object in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value one',
                                            'test_path_two' => 'value two',
                                            'test_path_three' => 'value three',
                                            'test_path_four' => 'value four',
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
                                            'new_path_one' => 'value one new',
                                            'new_path_two' => 'value two',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments/segment_one',
                    'value' => new DataObject([
                        'new_path_one' => 'value one new',
                        'new_path_two' => 'value two',
                    ])
                ]
            ],
            'replace nested object values with array of values in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'test_path_one' => 'value one',
                                            'test_path_two' => 'value two',
                                            'test_path_three' => 'value three',
                                            'test_path_four' => 'value four',
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
                                            'test_path_one' => 'value one new',
                                            'test_path_two' => 'value two',
                                            'test_path_three' => 'value three new',
                                            'test_path_four' => 'value four new',
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments/segment_one',
                    'value' => [
                        'test_path_one' => 'value one new',
                        'test_path_three' => 'value three new',
                        'test_path_four' => 'value four new',
                    ]
                ]
            ],
            'replace nested object array item with another value in the nested object structure' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'test_path' => 'value',
                            'segments' => [
                                'segment_one' => new DataObject([
                                    'segments' => [
                                        'segment_one' => new DataObject([
                                            'items' => [
                                                'item_one' => 'value one',
                                                'item_two' => 'value two',
                                            ]
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
                                            'items' => [
                                                'item_one' => 'value one',
                                                'item_two' => 'value two updated',
                                            ]
                                        ])
                                    ]
                                ])
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/segments/segment_one/segments/segment_one/items/item_two',
                    'value' => 'value two updated'
                ]
            ],
        ];
    }

    public function testReplaceNodeNotExists()
    {
        $replaceOperation = $this->createReplaceOperation([
            'path' => 'data/result',
            'value' => [
                'name' => 'product two',
                'sku' => 'product-two',
            ]
        ]);

        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook_replace');
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'The webhook operation was unable to replace a value by path "data/result" for ' .
                'hook "test_hook_replace": The path part "data" does not exist.'
            );
        $this->valueResolverMock->expects(self::never())
            ->method('resolve');
        $this->hookMock->expects(self::never())
            ->method('getFields');
        $this->valueConverterMock->expects(self::never())
            ->method('convert');
        $arguments = [];
        $replaceOperation->execute($arguments);
        self::assertEquals([], $arguments);
    }

    public function testAddValueException()
    {
        $this->expectException(OperationException::class);
        $this->expectExceptionMessage(
            'Unable to replace the value by path "result/shipping-methods" for hook "test_hook_replace"'
        );

        $replaceOperation = $this->createReplaceOperation([
            'path' => 'result/shipping-methods',
            'value' => 'two'
        ]);
        $arguments = [
            'result' => $replaceOperation
        ];

        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook_replace');
        $this->loggerMock->expects(self::never())
            ->method('debug');
        $this->valueResolverMock->expects(self::once())
            ->method('resolve')
            ->with([
                'path' => 'result/shipping-methods',
                'value' => 'two'
            ])
            ->willReturn('two');
        $this->hookMock->expects(self::once())
            ->method('getFields')
            ->willReturn(['field' => 'field_value']);
        $this->valueConverterMock->expects(self::once())
            ->method('convert')
            ->with('two', 'result/shipping-methods', ['field' => 'field_value'], $arguments)
            ->willReturn('two');

        $replaceOperation->execute($arguments);
        self::assertEquals([], $arguments);
    }

    public function testValueCanNotBeReplaced()
    {
        $replaceOperation = $this->createReplaceOperation([
            'path' => 'result/test',
            'value' => 'two'
        ]);
        $arguments = [
            'result' => [
            ]
        ];

        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook');
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with('The webhook operation was unable to replace a value by path "result/test" for hook "test_hook"');
        $this->valueResolverMock->expects(self::once())
            ->method('resolve')
            ->with([
                'path' => 'result/test',
                'value' => 'two'
            ])
            ->willReturn('two');
        $this->hookMock->expects(self::once())
            ->method('getFields')
            ->willReturn(['field' => 'field_value']);
        $this->valueConverterMock->expects(self::once())
            ->method('convert')
            ->with('two', 'result/test', ['field' => 'field_value'], $arguments)
            ->willReturn('two');

        $replaceOperation->execute($arguments);
        self::assertEquals(['result' => []], $arguments);
    }

    /**
     * Creates the Replace Operation
     *
     * @param array $configuration
     * @return Replace
     */
    private function createReplaceOperation(array $configuration): Replace
    {
        return new Replace(
            $this->hookMock,
            new CaseConverter(),
            $configuration,
            $this->valueResolverMock,
            new DataUpdater(new CaseConverter()),
            $this->valueConverterMock,
            $this->loggerMock
        );
    }
}
