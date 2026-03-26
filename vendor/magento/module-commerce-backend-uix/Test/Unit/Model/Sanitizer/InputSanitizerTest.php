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
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test class for InputSanitizer class
 */
class InputSanitizerTest extends TestCase
{
    private const EXTENSION_POINT_NAMESPACE = 'ext';
    private const EXPECTED_ERROR_MESSAGE =
        'Admin UI SDK - One or more registered ext failed due to missing mandatory fields. Mandatory fields [id,name]';
    private const ALLOWED_VALUES_ERROR_MESSAGE =
        'Admin UI SDK - One or more registered ext failed due to forbidden values for some fields.';

    private const REQUIRED_FIELDS = [
        'id',
        'name'
    ];

    private const ALLOWED_FIELD_VALUES = [
        'fib' => [
            '1', '2', '3', '5'
        ],
        'align' => [
            'left', 'center', 'right'
        ]
    ];

    /**
     * @var InputSanitizer
     */
    private InputSanitizer $inputSanitizer;

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

        $this->inputSanitizer = new InputSanitizer(
            $loggerHandler,
            self::EXTENSION_POINT_NAMESPACE,
            self::REQUIRED_FIELDS,
            self::ALLOWED_FIELD_VALUES
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
        $this->assertEquals([], $this->inputSanitizer->sanitize([]));
    }

    /**
     * Test sanitize missing required field
     *
     * @return void
     */
    public function testSanitizeMissingRequiredField(): void
    {
        $input = [
            [
                'name' => 'test',
                'path' => 'testPath'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::EXPECTED_ERROR_MESSAGE);
        $this->assertEquals([], $this->inputSanitizer->sanitize($input));
    }

    /**
     * Test sanitize no missing mandatory fields
     *
     * @return void
     */
    public function testSanitizeNoMissingMandatoryFields(): void
    {
        $input = [
            [
                'id' => 'id1',
                'name' => 'test',
                'path' => 'testPath'
            ]
        ];

        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals($input, $this->inputSanitizer->sanitize($input));
    }

    /**
     * Test sanitize multiple inputs with failure
     *
     * @return void
     */
    public function testSanitizeMultipleInputsWithFailure(): void
    {
        $input = [
            [
                'name' => 'test'
            ],
            [
                'id' => 'id1',
                'name' => 'testName'
            ],
            [
                'id' => 'id1'
            ]
        ];

        $expectedResult = [
            [
                'id' => 'id1',
                'name' => 'testName'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::EXPECTED_ERROR_MESSAGE);

        $this->assertEquals(
            $expectedResult,
            $this->inputSanitizer->sanitize($input)
        );
    }

    /**
     * Test sanitize with forbidden values for specific fields
     *
     * @return void
     */
    public function testSanitizeWithForbiddenValuesFibField(): void
    {
        $input = [
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 4
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::ALLOWED_VALUES_ERROR_MESSAGE);
        $this->assertEquals([], $this->inputSanitizer->sanitize($input));
    }

    /**
     * Test sanitize with allowed values for specific fields
     *
     * @return void
     */
    public function testSanitizeWithAllowedValuesFibField(): void
    {
        $input = [
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 3
            ]
        ];

        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals($input, $this->inputSanitizer->sanitize($input));
    }

    /**
     * Test sanitize with multiple fields, allowed and forbidden values
     *
     * @return void
     */
    public function testSanitizeWithMultipleFieldsAllowedAndForbiddenValues(): void
    {
        $input = [
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 4,
                'align' => 'center'
            ],
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 3,
                'align' => 'left'
            ],
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 3,
                'align' => 'somewhere'
            ]
        ];

        $expectedSanitizedInput = [
            [
                'id' => 'id1',
                'name' => 'testName',
                'fib' => 3,
                'align' => 'left'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::ALLOWED_VALUES_ERROR_MESSAGE);
        $this->assertEquals($expectedSanitizedInput, $this->inputSanitizer->sanitize($input));
    }
}
