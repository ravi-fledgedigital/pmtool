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
use Magento\CommerceBackendUix\Model\Sanitizer\MenuSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test class for MenuSanitizer class
 */
class MenuSanitizerTest extends TestCase
{
    private const MISSING_FILEDS_ERROR_MESSAGE =
        'Admin UI SDK - One or more registered menu items failed due to missing mandatory fields.'
        . ' Mandatory fields [id,title]';

    private const WRONG_ID_FORMAT_ERROR_MESSAGE =
        'Admin UI SDK - One or more registered menu items failed due to wrong id format.';

    /**
     * @var MenuSanitizer
     */
    private $menuSanitizer;

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
        $this->menuSanitizer = new MenuSanitizer(
            $loggerHandler,
            new InputSanitizer($loggerHandler, 'menu items', ['id', 'title'])
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
        $this->assertEquals([], $this->menuSanitizer->sanitizedMenuItems([]));
    }

    /**
     * Test sanitize menu id missing
     *
     * @return void
     */
    public function testSanitizeMenuIdMissing(): void
    {
        $menuItems = [
            [
                'title' => 'testTitle'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_FILEDS_ERROR_MESSAGE);
        $this->assertEquals([], $this->menuSanitizer->sanitizedMenuItems($menuItems));
    }

    /**
     * Test sanitize menu title missing
     *
     * @return void
     */
    public function testSanitizeMenuTitleMissing(): void
    {
        $menuItems = [
            [
                'id' => 'testId'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::MISSING_FILEDS_ERROR_MESSAGE);
        $this->assertEquals([], $this->menuSanitizer->sanitizedMenuItems($menuItems));
    }

    /**
     * Test sanitize no missing mandatory fields
     *
     * @return void
     */
    public function testSanitizeNoMissingMandatoryFields(): void
    {
        $menuItems = [
            [
                'id' => 'testId',
                'title' => 'testTitle'
            ]
        ];

        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals($menuItems, $this->menuSanitizer->sanitizedMenuItems($menuItems));
    }

    /**
     * Test sanitize wrong id format
     *
     * @return void
     */
    public function testSanitizeWrongIdFormat(): void
    {
        $menuItems = [
            [
                'id' => '!testId@',
                'title' => 'testTitle'
            ]
        ];

        $this->loggerMock->expects($this->once())->method('error')->with(self::WRONG_ID_FORMAT_ERROR_MESSAGE);
        $this->assertEquals([], $this->menuSanitizer->sanitizedMenuItems($menuItems));
    }

    /**
     * Test sanitize correct id format
     *
     * @return void
     */
    public function testSanitizeCorrectIdFormat(): void
    {
        $menuItems = [
            [
                'id' => '_testId:1/',
                'title' => 'testTitle'
            ]
        ];

        $this->loggerMock->expects($this->never())->method('error');
        $this->assertEquals($menuItems, $this->menuSanitizer->sanitizedMenuItems($menuItems));
    }
}
