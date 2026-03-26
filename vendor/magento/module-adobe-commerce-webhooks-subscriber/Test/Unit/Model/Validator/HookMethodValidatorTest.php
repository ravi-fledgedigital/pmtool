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

namespace Magento\AdobeCommerceWebhooksSubscriber\Test\Unit\Model\Validator;

use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookMethodValidator;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookMethodValidator
 */
class HookMethodValidatorTest extends TestCase
{
    /**
     * @var HookMethodValidator
     */
    private HookMethodValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookMethodValidator();
    }

    public function testValidateWithValidMethod(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookMethod')
            ->willReturn('observer.save_product');

        $this->validator->validate($hook);
    }

    /**
     * @param string $methodName
     * @return void
     * @throws ValidatorException
     */
    #[DataProvider('invalidMethodDataProvider')]
    public function testValidateWithInvalidMethod(string $methodName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'The webhook method can contain only alphanumeric characters, underscores and dots.'
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookMethod')
            ->willReturn($methodName);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function invalidMethodDataProvider(): array
    {
        return [
            ['observer.a\'];}'],
            ['observer.a"];}'],
            ['plugin.a\'];}'],
            ['plugin.a{}'],
            ['plugin .a{}'],
        ];
    }
}
