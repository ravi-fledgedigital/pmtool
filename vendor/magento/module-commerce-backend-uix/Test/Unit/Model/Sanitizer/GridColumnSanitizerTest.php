<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\CommerceBackendUix\Test\Unit\Model\Sanitizer;

use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\CommerceBackendUix\Model\Sanitizer\GridColumnSanitizer;
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test class for GridColumnSanitizer class
 */
class GridColumnSanitizerTest extends TestCase
{
    private const MISSING_COMMON_DATA_ERROR_MESSAGE =
        'Admin UI SDK - Failed to register grid columns. Missing mandatory fields in registration.';

    private const MISSING_REQUIRED_FIELDS_ERROR_MESSAGE =
        'Admin UI SDK - One or more registered gridColumn failed due to missing mandatory fields.'
        . ' Mandatory fields [columnId,label,type,align]';

    /**
     * @var GridColumnSanitizer
     */
    private $gridColumnSanitizer;

    /**
     * @var MockObject|(LoggerInterface&MockObject)
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $loggerHandler = new LoggerHandler($this->loggerMock);

        $this->gridColumnSanitizer = new GridColumnSanitizer(
            $loggerHandler,
            new InputSanitizer($loggerHandler, 'gridColumn', ['columnId', 'label', 'type', 'align'])
        );
    }

    /**
     * Test sanitize empty array
     *
     * @return void
     */
    public function testSanitizeEmptyArray(): void
    {
        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals([], $this->gridColumnSanitizer->sanitize([]));
    }

    /**
     * Test sanitize missing data
     *
     * @return void
     */
    public function testSanitizeMissingData(): void
    {
        $gridColumns = [
            'properties' => [
                [
                    'label' => 'testLabel',
                    'columnId' => 'testColumnId',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_COMMON_DATA_ERROR_MESSAGE);
        $this->assertEquals([], $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test sanitize missing properties
     *
     * @return void
     */
    public function testSanitizeMissingProperties(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_COMMON_DATA_ERROR_MESSAGE);
        $this->assertEquals([], $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test sanitize missing label
     *
     * @return void
     */
    public function testSanitizeMissingLabel(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'columnId' => 'testColumnId',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $expectedColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => []
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_REQUIRED_FIELDS_ERROR_MESSAGE);
        $this->assertEquals($expectedColumns, $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test sanitize missing columnId
     *
     * @return void
     */
    public function testSanitizeMissingColumnId(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $expectedColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => []
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_REQUIRED_FIELDS_ERROR_MESSAGE);
        $this->assertEquals($expectedColumns, $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test type missing
     *
     * @return void
     */
    public function testSanitizeTypeMissing(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'columnId' => 'testColumnId',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $expectedColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => []
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_REQUIRED_FIELDS_ERROR_MESSAGE);
        $this->assertEquals($expectedColumns, $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test align missing
     *
     * @return void
     */
    public function testSanitizeAlignMissing(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'columnId' => 'testColumnId',
                    'type' => 'testType',
                ]
            ]
        ];

        $expectedColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => []
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_REQUIRED_FIELDS_ERROR_MESSAGE);
        $this->assertEquals($expectedColumns, $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test mesh_id missing
     *
     * @return void
     */
    public function testSanitizeMeshIdMissing(): void
    {
        $gridColumns = [
            'data' => [
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_COMMON_DATA_ERROR_MESSAGE);
        $this->assertEquals([], $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test sanitize no missing mandatory fields
     *
     * @return void
     */
    public function testSanitizeNoMissingMandatoryFields(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'columnId' => 'testColumnId',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals($gridColumns, $this->gridColumnSanitizer->sanitize($gridColumns));
    }

    /**
     * Test sanitize multiple gridColumns with failure
     *
     * @return void
     */
    public function testSanitizeMultipleGridColumnsWithFailure(): void
    {
        $gridColumns = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'label' => 'testLabel',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ],
                [
                    'columnId' => 'testColumnId',
                    'label' => 'testLabel',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ],
                [
                    'columnId' => 'testColumnId',
                    'label' => 'testLabel',
                    'type' => 'testType'
                ]
            ]
        ];

        $expectedResult = [
            'data' => [
                'meshId' => 'testMeshId',
                'apiKey' => 'testApiKey'
            ],
            'properties' => [
                [
                    'columnId' => 'testColumnId',
                    'label' => 'testLabel',
                    'type' => 'testType',
                    'align' => 'testAlign'
                ]
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_REQUIRED_FIELDS_ERROR_MESSAGE);
        $this->assertEquals($expectedResult, $this->gridColumnSanitizer->sanitize($gridColumns));
    }
}
