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
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookRequiredFieldsValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookRequiredFieldsValidator
 */
class HookRequiredFieldsValidatorTest extends TestCase
{
    /**
     * @var HookRequiredFieldsValidator
     */
    private HookRequiredFieldsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookRequiredFieldsValidator();
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithValidType(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(4))
            ->method('getData')
            ->willReturn('some_value');
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::URL => 'url'
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithValidTypeAndConfiguredFields(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(4))
            ->method('getData')
            ->willReturn('some_value');
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([]);

        $validator = new HookRequiredFieldsValidator(requiredHookDataFields: []);
        $validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateWithMissedConfiguredField(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('The field "priority" is required and can not be empty.');

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(4))
            ->method('getData')
            ->willReturn('some_value');
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::URL => 'url'
            ]);

        $validator = new HookRequiredFieldsValidator(requiredHookDataFields: [
            WebhookDataInterface::URL,
            WebhookDataInterface::PRIORITY
        ]);
        $validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateMissedRequiredFieldUrl(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('The field "url" is required and can not be empty.');

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(4))
            ->method('getData')
            ->willReturn('some_value');
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateMissedRequiredFieldWebhookMethod(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('The field "webhook_method" is required and can not be empty.');

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getData')
            ->willReturn('');
        $hook->expects(self::never())
            ->method('getHookData');

        $this->validator->validate($hook);
    }
}
