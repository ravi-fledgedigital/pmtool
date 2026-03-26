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

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookNonNegativeNumberValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookNonNegativeNumberValidator
 */
class HookNonNegativeNumberValidatorTest extends TestCase
{
    /**
     * @var HookNonNegativeNumberValidator
     */
    private HookNonNegativeNumberValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookNonNegativeNumberValidator();
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithValidType(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::TIMEOUT => '33',
                WebhookDataInterface::SOFT_TIMEOUT => '333',
                WebhookDataInterface::PRIORITY => '33',
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     *
     */
    #[DataProvider('validateWithInvalidTypeDataProvider')]
    public function testValidateWithInvalidValue(array $hookData): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('The field value "%s" must be equal to or greater than 0.', array_key_first($hookData))
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn($hookData);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function validateWithInvalidTypeDataProvider(): array
    {
        return [
            [
                [
                    WebhookDataInterface::TIMEOUT => '-33',
                ],
            ],
            [
                [
                    WebhookDataInterface::SOFT_TIMEOUT => '-33',
                ],
            ],
            [
                [
                    WebhookDataInterface::TTL => '-33',
                ],
            ]
        ];
    }
}
