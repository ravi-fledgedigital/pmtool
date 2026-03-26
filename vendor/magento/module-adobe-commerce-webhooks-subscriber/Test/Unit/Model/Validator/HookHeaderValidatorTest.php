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

use Magento\AdobeCommerceWebhooks\Api\Data\HookHeaderInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookHeaderValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookHeaderValidator
 */
class HookHeaderValidatorTest extends TestCase
{
    /**
     * @var HookHeaderValidator
     */
    private HookHeaderValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookHeaderValidator();
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithoutHeaders(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test'
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithValidHeaders(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::HEADERS => $this->getDefaultHeaders()
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     *
     */
    #[DataProvider('validateWithMissingHeaderFieldDataProvider')]
    public function testValidateWithMissingHeaderField(string $fieldName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('Header field "%s" is required and can not be empty.', $fieldName)
        );

        $headers = $this->getDefaultHeaders();
        unset($headers[0][$fieldName]);
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::HEADERS => $headers
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     *
     */
    #[DataProvider('validateWithMissingHeaderFieldDataProvider')]
    public function testValidateWithEmptyHeaderFieldDataProvider(string $fieldName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('Header field "%s" is required and can not be empty.', $fieldName)
        );

        $headers = $this->getDefaultHeaders();
        $headers[0][$fieldName] = '';
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::HEADERS => $headers
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function validateWithMissingHeaderFieldDataProvider(): array
    {
        return [
            [HookHeaderInterface::NAME],
            [HookHeaderInterface::VALUE]
        ];
    }

    /**
     * Returns list of headers
     *
     * @return array
     */
    private function getDefaultHeaders(): array
    {
        return [
            [
                HookHeaderInterface::NAME => 'header_one',
                HookHeaderInterface::VALUE => 'value_one',
            ],
            [
                HookHeaderInterface::NAME => 'header_two',
                HookHeaderInterface::VALUE => 'value_two',
            ],
            [
                HookHeaderInterface::NAME => 'header_three',
                HookHeaderInterface::VALUE => 'value_three',
            ],
        ];
    }
}
