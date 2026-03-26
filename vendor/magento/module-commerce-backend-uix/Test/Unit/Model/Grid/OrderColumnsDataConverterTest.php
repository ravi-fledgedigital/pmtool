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

namespace Magento\CommerceBackendUix\Test\Unit\Model\Grid;

use Magento\CommerceBackendUix\Model\Grid\ColumnsDataConverter;
use Magento\CommerceBackendUix\Model\Grid\ColumnsDataFormatter;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test class for OrderColumnsDataConverter class
 */
class OrderColumnsDataConverterTest extends TestCase
{
    private const COLUMN_ID = 'test_column';
    private const COLUMN_TYPE = 'string';
    private const ENTITY = 'orders';
    private const GRID_COLUMN = 'orderGridColumns';

    /**
     * @var Json&MockObject|MockObject
     */
    private $jsonMock;

    /**
     * @var ColumnsDataConverter
     */
    private $converter;

    /**
     * @var LoggerInterface&MockObject|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $loggerHandler = new LoggerHandler($this->loggerMock);

        $this->jsonMock = $this->createMock(Json::class);
        $this->converter = new ColumnsDataConverter(
            $loggerHandler,
            $this->jsonMock,
            new ColumnsDataFormatter()
        );
    }

    /**
     * Test data conversion when receiving properly formatted data
     *
     * @return void
     */
    public function testReceivesExpectedDataSource(): void
    {
        $receivedData = '{"data":{
                "orders":{
                    "orderGridColumns":{
                        "1":{
                            "test_column":"value_1"
                        },
                        "2":{
                            "test_column":2
                        },
                        "3":{
                            "test_column":"value_3"
                        }
                    }
                }
            },
            "extensions":{}
        }';

        $unserializedData = [
            'data' =>
            [
                'orders' =>
                [
                    'orderGridColumns' =>
                    [
                        '1' =>
                        [
                            'test_column' => 'value_1',
                        ],
                        '2' =>
                        [
                            'test_column' => 2,
                        ],
                        '3' =>
                        [
                            'test_column' => 'value_3',
                        ],
                    ],
                ],
            ],
            'extensions' =>
            [
            ],
        ];

        $expectedData = [
            'data' =>
            [
                'orders' =>
                [
                    'orderGridColumns' =>
                    [
                        '1' =>
                        [
                            'test_column' => 'value_1',
                        ],
                        '2' =>
                        [
                            'test_column' => '',
                        ],
                        '3' =>
                        [
                            'test_column' => 'value_3',
                        ],
                    ],
                ],
            ],
            'extensions' =>
            [
            ],
        ];

        $this->jsonMock->expects($this->once())->method('unserialize')->willReturn($unserializedData);

        $this->assertSame(
            $expectedData,
            $this->converter->convertData(
                $receivedData,
                self::COLUMN_ID,
                self::COLUMN_TYPE,
                self::ENTITY,
                self::GRID_COLUMN
            )
        );
    }

    /**
     * Test data conversion when receiving data in a format that is not supported
     *
     * @return void
     */
    public function testReceivesMisformattedDatasource(): void
    {
        $receivedData = '{"data":{"test":{"testorders":1}}';

        $unserializedData = [
            'data' =>
            [
              'test' =>
                [
                    'testorders' => 1,
                ],
            ],
        ];
        $this->jsonMock->expects($this->once())->method('unserialize')->willReturn($unserializedData);

        $this->assertSame(
            [],
            $this->converter->convertData(
                $receivedData,
                self::COLUMN_ID,
                self::COLUMN_TYPE,
                self::ENTITY,
                self::GRID_COLUMN
            )
        );
    }

    /**
     * Test data conversion when receiving an empty data source
     *
     * @return void
     */
    public function testReceivesEmptyDatasource(): void
    {
        $this->jsonMock->expects($this->once())->method('unserialize')->willReturn([]);

        $this->assertSame(
            [],
            $this->converter->convertData('', self::COLUMN_ID, self::COLUMN_TYPE, self::ENTITY, self::GRID_COLUMN)
        );
    }
}
