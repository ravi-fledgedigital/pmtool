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

use Magento\AdobeCommerceWebhooks\Model\Validator\WebhookAllowedValidatorInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookAllowedMethodValidator;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookAllowedMethodValidator
 */
class HookAllowedMethodValidatorTest extends TestCase
{
    /**
     * @var HookAllowedMethodValidator
     */
    private HookAllowedMethodValidator $validator;

    /**
     * @var WebhookAllowedValidatorInterface|MockObject;
     */
    private WebhookAllowedValidatorInterface|MockObject $webhookAllowedValidatorMock;

    protected function setUp(): void
    {
        $this->webhookAllowedValidatorMock = $this->createMock(WebhookAllowedValidatorInterface::class);
        $this->validator = new HookAllowedMethodValidator($this->webhookAllowedValidatorMock);
    }

    public function testValidateWithAllowedMethod(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookMethod')
            ->willReturn('allowed.method');
        $this->webhookAllowedValidatorMock->expects(self::once())
            ->method('validate')
            ->with('allowed.method');

        $this->validator->validate($hook);
    }

    public function testValidateWithNotAllowedMethod(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Method not allowed');

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookMethod')
            ->willReturn('not.allowed.method');
        $this->webhookAllowedValidatorMock->expects(self::once())
            ->method('validate')
            ->with('not.allowed.method')
            ->willThrowException(new ValidatorException(__('Method not allowed')));

        $this->validator->validate($hook);
    }
}
