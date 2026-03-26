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

use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorFactory;
use Magento\AdobeCommerceWebhooks\Api\Data\HookRuleInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookRuleValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookRuleValidator
 */
class HookRuleValidatorTest extends TestCase
{
    /**
     * @var OperatorFactory|MockObject
     */
    private $operatorFactoryMock;

    /**
     * @var HookRuleValidator
     */
    private HookRuleValidator $validator;

    protected function setUp(): void
    {
        $this->operatorFactoryMock = $this->createMock(OperatorFactory::class);
        $this->validator = new HookRuleValidator($this->operatorFactoryMock);
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithoutRules(): void
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
    public function testValidateWithValidRules(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $this->getDefaultRules()
            ]);
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('getOperatorsList')
            ->willReturn(['operator_one', 'operator_two']);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     * @throws ValidatorException
     */
    public function testValidateWithNotValidOperator(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Rule operator operator_two is invalid. Allowed operators: [operator_one]');

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $this->getDefaultRules()
            ]);
        $this->operatorFactoryMock->expects(self::exactly(3))
            ->method('getOperatorsList')
            ->willReturn(['operator_one']);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     *
     */
    #[DataProvider('validateWithInvalidTypeDataProvider')]
    public function testValidateWithMissingField(string $fieldName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(sprintf('Rule field "%s" is required and can not be empty.', $fieldName));

        $rules = $this->getDefaultRules();
        unset($rules[0][$fieldName]);
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $rules
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws Exception
     *
     */
    #[DataProvider('validateWithInvalidTypeDataProvider')]
    public function testValidateWithEmptyField(string $fieldName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(sprintf('Rule field "%s" is required and can not be empty.', $fieldName));

        $rules = $this->getDefaultRules();
        $rules[0][$fieldName] = '';
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $rules
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function validateWithInvalidTypeDataProvider(): array
    {
        return [
            [HookRuleInterface::FIELD],
            [HookRuleInterface::OPERATOR],
        ];
    }

    /**
     * @throws Exception
     */
    public function testValidateWithMissedValueField(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Rule field "value" is required.');

        $rules = $this->getDefaultRules();
        unset($rules[0][HookRuleInterface::VALUE]);
        unset($rules[1][HookRuleInterface::VALUE]);
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $rules
            ]);

        $this->validator->validate($hook);
    }

    /**
     * @throws ValidatorException
     * @throws Exception
     */
    public function testValidateWithEmptyValueField(): void
    {
        $rules = $this->getDefaultRules();
        $rules[0][HookRuleInterface::VALUE] = '';
        $rules[1][HookRuleInterface::VALUE] = '0';
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('getOperatorsList')
            ->willReturn(['operator_one', 'operator_two']);
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn([
                WebhookDataInterface::WEBHOOK_METHOD => 'test',
                WebhookDataInterface::RULES => $rules
            ]);

        $this->validator->validate($hook);
    }

    /**
     * Returns list of rules
     *
     * @return array
     */
    private function getDefaultRules(): array
    {
        return [
            [
                HookRuleInterface::FIELD => 'field_one',
                HookRuleInterface::OPERATOR => 'operator_one',
                HookRuleInterface::VALUE => 'value_one',
            ],
            [
                HookRuleInterface::FIELD => 'field_two',
                HookRuleInterface::OPERATOR => 'operator_two',
                HookRuleInterface::VALUE => 'value_two',
            ]
        ];
    }
}
