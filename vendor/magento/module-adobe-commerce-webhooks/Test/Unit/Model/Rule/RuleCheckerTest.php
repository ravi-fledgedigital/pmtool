<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Rule;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceWebhooks\Model\Filter\ContextRetriever;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorFactory;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorInterface;
use Magento\AdobeCommerceWebhooks\Model\Rule\RuleChecker;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookRule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see RuleChecker
 */
class RuleCheckerTest extends TestCase
{
    /**
     * @var OperatorFactory|MockObject
     */
    private OperatorFactory|MockObject $operatorFactoryMock;

    /**
     * @var ContextRetriever|MockObject
     */
    private ContextRetriever|MockObject $contextRetrieverMock;

    /**
     * @var ContextPool|MockObject
     */
    private ContextPool|MockObject $contextPoolMock;

    /**
     * @var Hook|MockObject
     */
    private Hook|MockObject $hookMock;

    /**
     * @var RuleChecker
     */
    private RuleChecker $ruleChecker;

    protected function setUp(): void
    {
        $this->operatorFactoryMock = $this->createMock(OperatorFactory::class);
        $this->contextRetrieverMock = $this->createMock(ContextRetriever::class);
        $this->contextPoolMock = $this->createMock(ContextPool::class);
        $this->hookMock = $this->createMock(Hook::class);

        $this->ruleChecker = new RuleChecker(
            $this->operatorFactoryMock,
            $this->contextRetrieverMock,
            $this->contextPoolMock
        );
    }

    public function testRulesAreEmpty()
    {
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([]);
        $this->operatorFactoryMock->expects(self::never())
            ->method('create');

        self::assertTrue($this->ruleChecker->verify($this->hookMock, ['test' => 3]));
    }

    public function testFieldValueExist()
    {
        $hookRule = new HookRule([
            'field' => 'status',
            'operator' => 'notEqual',
            'value' => '2'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRule]);
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('3', $hookRule->getValue())
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($hookRule->getOperator())
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->hookMock, [
            'order_id' => 123,
            'status' => '3'
        ]));
    }

    public function testMultipleRulesWithRemove()
    {
        $hookRuleOne = new HookRule([
            'field' => 'status',
            'operator' => 'notEqual',
            'value' => '3',
            'remove' => 'true'
        ]);
        $hookRuleTwo = new HookRule([
            'field' => 'order_id',
            'operator' => 'greaterThan',
            'value' => '0'
        ]);

        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRuleOne, $hookRuleTwo]);
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with(123, $hookRuleTwo->getValue())
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($hookRuleTwo->getOperator())
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->hookMock, [
            'order_id' => 123,
            'status' => '3'
        ]));
    }

    public function testNestedFieldValueExist()
    {
        $hookRule = new HookRule([
            'field' => 'data.order.payment.status',
            'operator' => 'equal',
            'value' => '2'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRule]);
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('2', $hookRule->getValue())
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($hookRule->getOperator())
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->hookMock, [
            'data' => [
                'order' => [
                    'payment' => [
                        'id' => 333,
                        'status' => 2
                    ]
                ]
            ]
        ]));
    }

    public function testNestedFieldValueExistMultipleRules()
    {
        $hookRuleOne = new HookRule([
            'field' => 'data.order.payment.status',
            'operator' => 'equal',
            'value' => '2'
        ]);
        $hookRuleTwo = new HookRule([
            'field' => 'data.order.payment.invoice.status',
            'operator' => 'equal',
            'value' => '3'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRuleOne, $hookRuleTwo]);
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(
                function (mixed $fieldValue, ?string $ruleValue = null) use ($hookRuleOne, $hookRuleTwo) {
                    static $count = 0;
                    switch ($count++) {
                        case 0:
                            self::assertEquals('3', $fieldValue);
                            self::assertEquals($hookRuleOne->getValue(), $ruleValue);
                            break;
                        case 1:
                            self::assertEquals('4', $fieldValue);
                            self::assertEquals($hookRuleTwo->getValue(), $ruleValue);
                            break;
                    };
                    return true;
                }
            );
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $operatorName) use ($hookRuleOne, $hookRuleTwo, $operatorMock) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($operatorName, $hookRuleOne->getOperator());
                        break;
                    case 1:
                        self::assertEquals($operatorName, $hookRuleTwo->getOperator());
                        break;
                };
                return $operatorMock;
            });

        self::assertTrue($this->ruleChecker->verify($this->hookMock, [
            'data' => [
                'order' => [
                    'payment' => [
                        'id' => 333,
                        'status' => 3,
                        'invoice' => [
                            'id' => 123,
                            'status' => 4,
                        ]
                    ]
                ]
            ]
        ]));
    }

    public function testNestedFieldValueDoesNotExist()
    {
        $hookRule = new HookRule([
            'field' => 'data.order.payment.status',
            'operator' => 'equal',
            'value' => '2'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRule]);
        $this->operatorFactoryMock->expects(self::never())
            ->method('create');

        self::assertFalse($this->ruleChecker->verify($this->hookMock, [
            'order_id' => 3,
            'status' => 'pending',
            'data' => 'test'
        ]));
    }

    public function testInValidContextFieldExist()
    {
        $hookRule = new HookRule([
            'field' => 'customer_session',
            'operator' => 'equal',
            'value' => 'test1@abc.com'
        ]);
        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRule]);

        $expectedContext = 'test1@abc.com';

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('customer_session')
            ->willReturn(false);

        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue')
            ->with($hookRule->getField(), $this->hookMock)
            ->willReturn($expectedContext);

        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::never())
            ->method('verify')
            ->with($expectedContext, $hookRule->getValue())
            ->willReturn(true);

        $this->operatorFactoryMock->expects(self::never())
            ->method('create')
            ->with($hookRule->getOperator())
            ->willReturn($operatorMock);

        self::assertFalse($this->ruleChecker->verify($this->hookMock, [
            'email' => 'abc.com',
            'name'  => 'test'
        ]));
    }

    public function testContextFieldWithMultipleRules()
    {
        $hookRuleOne = new HookRule([
            'field' => 'data.order.payment.status',
            'operator' => 'equal',
            'value' => '3'
        ]);
        $hookRuleTwo = new HookRule([
            'field' => 'context_customer_session.get_customer.get_email',
            'operator' => 'equal',
            'value' => 'test@abc.com'
        ]);

        $this->hookMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$hookRuleOne, $hookRuleTwo]);
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(
                function (mixed $fieldValue, ?string $ruleValue = null) use ($hookRuleOne, $hookRuleTwo) {
                    static $count = 0;
                    switch ($count++) {
                        case 0:
                            self::assertEquals('3', $fieldValue);
                            self::assertEquals($hookRuleOne->getValue(), $ruleValue);
                            break;
                        case 1:
                            self::assertEquals('test@abc.com', $fieldValue);
                            self::assertEquals($hookRuleTwo->getValue(), $ruleValue);
                            break;
                    };
                    return true;
                }
            );
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $operatorName) use ($hookRuleOne, $hookRuleTwo, $operatorMock) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($operatorName, $hookRuleOne->getOperator());
                        break;
                    case 1:
                        self::assertEquals($operatorName, $hookRuleTwo->getOperator());
                        break;
                };
                return $operatorMock;
            });

        $this->contextPoolMock->expects(self::exactly(2))
            ->method('has')
            ->willReturnCallback(function (string $fieldName) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('data', $fieldName);
                        return false;
                    case 1:
                        self::assertEquals('context_customer_session', $fieldName);
                        return true;
                }
                return false;
            });

        $this->contextRetrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($hookRuleTwo->getField(), $this->hookMock)
            ->willReturn('test@abc.com');

        self::assertTrue($this->ruleChecker->verify($this->hookMock, [
            'data' => [
                'order' => [
                    'payment' => [
                        'id' => 333,
                        'status' => 3
                    ]
                ]
            ]
        ]));
    }
}
