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

use Magento\AdminUiSdkCustomFees\Model\CustomFeeParser;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for CustomFeeParser class
 */
class CustomFeeParserTest extends TestCase
{
    private const ADMIN_UI_SDK_PREFIX = 'Admin UI SDK - ';

    private const ERROR_MESSAGE =
        'One or more registered custom fees failed due to missing mandatory fields. Mandatory fields [id,label,value]';

    /**
     * @var CustomFeeParser
     */
    private $customFeeParser;

    /**
     * @var MockObject|(LoggerInterface&MockObject)
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $loggerHandler = new LoggerHandler($this->loggerMock);

        $sanitizer = new InputSanitizer(
            $loggerHandler,
            'custom fees',
            ['id', 'label', 'value']
        );
        $this->customFeeParser = new CustomFeeParser($sanitizer);
    }

    /**
     * Test parse method with empty registrations
     *
     * @return void
     */
    public function testParseEmptyRegistrations(): void
    {
        $parsedRegistrations = [];

        $this->customFeeParser->parse([], $parsedRegistrations, 'extension id');

        $this->assertEquals([], $parsedRegistrations);
    }

    /**
     * Test parse method with correct custom fee
     *
     * @return void
     */
    public function testParseCorrectCustomFee(): void
    {
        $loadedRegistrations = [
            'order' => [
                'customFees' => [
                    [
                        'id' => 'fee id',
                        'label' => 'fee label',
                        'value' => 'fee value'
                    ]
                ]
            ]
        ];
        $parsedRegistrations = [];

        $this->customFeeParser->parse($loadedRegistrations, $parsedRegistrations, 'extension id');

        $this->assertEquals(
            [
                'order' => [
                    'customFees' => [
                        [
                            'id' => 'fee id',
                            'label' => 'fee label',
                            'value' => 'fee value'
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse method with incorrect custom fee
     *
     * @return void
     */
    public function testParseInvalidCustomFee(): void
    {
        $loadedRegistrations = [
            'order' => [
                'customFees' => [
                    [
                        'label' => 'fee label',
                        'value' => 'fee value'
                    ]
                ]
            ]
        ];
        $parsedRegistrations = [];

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(sprintf('%s%s', self::ADMIN_UI_SDK_PREFIX, self::ERROR_MESSAGE));

        $this->customFeeParser->parse($loadedRegistrations, $parsedRegistrations, 'extension id');

        $this->assertEquals([], $parsedRegistrations);
    }
}
