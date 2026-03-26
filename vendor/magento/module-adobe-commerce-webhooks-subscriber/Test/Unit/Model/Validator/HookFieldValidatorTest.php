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

use Magento\AdobeCommerceWebhooks\Api\Data\HookFieldInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookFieldValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookFieldValidator
 */
class HookFieldValidatorTest extends TestCase
{
    /**
     * @var HookFieldValidator
     */
    private HookFieldValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookFieldValidator();
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithoutFields(): void
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
    public function testValidateWithValidFields(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::FIELDS => $this->getDefaultFields()
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateWithMissingField(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('The "%s" for the hook field is required and can not be empty.', HookFieldInterface::NAME)
        );

        $fields = $this->getDefaultFields();
        unset($fields[0][HookFieldInterface::NAME]);
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::FIELDS => $fields
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     */
    public function testValidateWithEmptyFieldName(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf('The "%s" for the hook field is required and can not be empty.', HookFieldInterface::NAME)
        );

        $fields = $this->getDefaultFields();
        $fields[0][HookFieldInterface::NAME] = '';
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::FIELDS => $fields
            ]);

        $this->validator->validate($hook);
    }

    /**
     * Returns list of fields
     *
     * @return array
     */
    private function getDefaultFields(): array
    {
        return [
            [
                HookFieldInterface::NAME => 'field_one',
                HookFieldInterface::SOURCE => 'source_one',
            ],
            [
                HookFieldInterface::NAME => 'field_two',
                HookFieldInterface::SOURCE => 'source_two',
            ],
            [
                HookFieldInterface::NAME => 'field_three',
                HookFieldInterface::SOURCE => 'source_three',
            ]
        ];
    }
}
