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

use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookTypeValidator;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookTypeValidator
 */
class HookTypeValidatorTest extends TestCase
{
    /**
     * @var HookTypeValidator
     */
    private HookTypeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookTypeValidator();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithValidType(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookType')
            ->willReturn(Webhook::TYPE_BEFORE);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateWithInvalidType(): void
    {
        $this->expectException(ValidatorException::class);

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getWebhookType')
            ->willReturn('invalid-type');

        $this->validator->validate($hook);
    }
}
