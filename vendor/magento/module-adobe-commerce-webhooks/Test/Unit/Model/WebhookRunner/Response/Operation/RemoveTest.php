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
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation\Remove;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\Framework\DataObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see Remove class
 */
class RemoveTest extends TestCase
{
    /**
     * @var Hook|MockObject
     */
    private Hook|MockObject $hookMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->hookMock = $this->createMock(Hook::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    /**
     * @param array $arguments
     * @param array $replacedArguments
     * @param array $configuration
     * @return void
     */
    #[DataProvider('removeDataProvider')]
    public function testRemove(array $arguments, array $replacedArguments, array $configuration)
    {
        $removeOperation = $this->createRemoveOperation($configuration);
        $this->loggerMock->expects(self::never())
            ->method('debug');
        $this->hookMock->expects(self::never())
            ->method('getName');

        $removeOperation->execute($arguments);
        self::assertEquals($replacedArguments, $arguments);
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function removeDataProvider(): array
    {
        return [
            'remove nested scalar value' => [
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one',
                            'sku' => 'test sku'
                        ]
                    ]
                ],
                [
                    'data' => [
                        'product' => [
                            'sku' => 'test sku'
                        ]
                    ]
                ],
                [
                    'path' => 'data/product/name',
                ]
            ],
            'remove nested array value' => [
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one',
                            'category_ids' => [
                                '2',
                                '5'
                            ]
                        ]
                    ]
                ],
                [
                    'data' => [
                        'product' => [
                            'name' => 'product one'
                        ]
                    ]
                ],
                [
                    'path' => 'data/product/category_ids'
                ]
            ],
            'root remove' => [
                ['result' => 3],
                [],
                [
                  'path' => 'result',
                ]
            ],
            'remove from object' => [
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value'])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([])
                    ]
                ],
                [
                    'path' => 'data/result/test_path',
                ]
            ],
            'remove from object array' => [
                [
                    'data' => [
                        'result' => new DataObject([
                            'items' => [
                                'items1' => 'value',
                                'items2' => 'value'
                            ]
                        ])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject([
                            'items' => [
                                'items2' => 'value'
                            ]
                        ])
                    ]
                ],
                [
                    'path' => 'data/result/items/items1',
                ]
            ],
            'remove from object array with invalid path' => [
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value'])
                    ]
                ],
                [
                    'data' => [
                        'result' => new DataObject(['test_path' => 'value'])
                    ]
                ],
                [
                    'path' => 'data/result/path',
                ]
            ]
        ];
    }

    /**
     * @param array $arguments
     * @param array $replacedArguments
     * @param array $configuration
     * @return void
     */
    #[DataProvider('removeNonExistentDataProvider')]
    public function testRemoveNonExistentValue(array $arguments, array $replacedArguments, array $configuration)
    {
        $removeOperation = $this->createRemoveOperation($configuration);
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'The webhook operation was unable to remove a value with path "data/result/test" for hook "test_hook"'
            );
        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook');

        $removeOperation->execute($arguments);
        self::assertEquals($replacedArguments, $arguments);
    }

    /**
     * @return array[]
     */
    public static function removeNonExistentDataProvider(): array
    {
        return [
            'remove with non-existent array key' => [
                [
                    'data' => [
                        'result' => [
                            'name' => 'product one',
                        ]
                    ]
                ],
                [
                    'data' => [
                        'result' => [
                            'name' => 'product one',
                        ]
                    ]
                ],
                [
                    'path' => 'data/result/test',
                ]
            ],
            'remove from non-array value' => [
                [
                    'data' => [
                        'result' => 'result'
                    ]
                ],
                [
                    'data' => [
                        'result' => 'result'
                    ]
                ],
                [
                    'path' => 'data/result/test'
                ]
            ]
        ];
    }

    public function testRemoveNoUnsetMethod()
    {
        $configuration = [
            'path' => 'data/result/test'
        ];
        $data = [
            'data' => [
                'result' => new class{
                }
            ]
        ];

        $this->expectException(OperationException::class);
        $this->expectExceptionMessage('Unable to remove a value with path "data/result/test" for hook "test_hook"');

        $removeOperation = $this->createRemoveOperation($configuration);
        $this->loggerMock->expects(self::never())
            ->method('debug');
        $this->hookMock->expects(self::once())
            ->method('getName')
            ->willReturn('test_hook');

        $removeOperation->execute($data);
    }

    /**
     * Creates a Remove operation object
     *
     * @param array $configuration
     * @return Remove
     */
    private function createRemoveOperation(array $configuration): Remove
    {
        return new Remove(
            $this->hookMock,
            $configuration,
            new DataUpdater(new CaseConverter()),
            $this->loggerMock
        );
    }
}
