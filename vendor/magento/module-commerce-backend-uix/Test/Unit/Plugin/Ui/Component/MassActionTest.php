<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Test\Unit\Plugin\Ui\Component;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\CommerceBackendUix\Plugin\Ui\Component\MassAction;
use Magento\CommerceBackendUix\Ui\Component\MassAction as ComponentMassAction;
use Magento\Framework\AuthorizationInterface;
use Magento\Ui\Component\MassAction as BaseMassAction;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mass Action Unit Tests
 */
class MassActionTest extends TestCase
{
    /**
     * @var MassAction
     */
    private $massAction;

    /**
     * @var Config&MockObject
     */
    private $configMock;

    /**
     * @var ComponentMassAction&MockObject
     */
    private $componentMassActionMock;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private AuthorizationInterface $authorizationInterfaceMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->componentMassActionMock = $this->createMock(ComponentMassAction::class);
        $this->authorizationInterfaceMock =
            $this->getMockBuilder(AuthorizationInterface::class)->getMockForAbstractClass();
        $this->massAction = new MassAction(
            $this->componentMassActionMock,
            new AuthorizationValidator($this->configMock, $this->authorizationInterfaceMock)
        );
    }

    /**
     * Test afterPrepare admin ui sdk disabled
     *
     * @return void
     */
    public function testAfterPrepareAdminUiSdkDisabled()
    {
        $baseMassAction = $this->createMock(BaseMassAction::class);
        $context = $this->createMock(ContextInterface::class);

        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(false);
        $this->authorizationInterfaceMock->expects($this->never())->method('isAllowed')->willReturn(true);
        $baseMassAction->expects($this->exactly(0))->method('getContext');
        $context->expects($this->exactly(0))->method('getNamespace');
        $baseMassAction->expects($this->exactly(0))->method('getConfiguration');
        $baseMassAction->expects($this->exactly(0))->method('setData');
        $this->componentMassActionMock->expects($this->exactly(0))->method('getMassActionsConfig');

        $this->massAction->afterPrepare($baseMassAction);
    }

    /**
     * Test afterPrepare admin ui sdk enabled
     *
     * @return void
     */
    public function testAfterPrepareAdminUiSdkEnabled()
    {
        $baseMassAction = $this->createMock(BaseMassAction::class);
        $context = $this->createMock(ContextInterface::class);

        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $baseMassAction->expects($this->once())->method('getContext')->willReturn($context);
        $context->expects($this->once())->method('getNamespace')->willReturn(UiGridType::SALES_ORDER_GRID);
        $baseMassAction->expects($this->once())->method('getConfiguration')->willReturn(
            [
                'actions' => []
            ]
        );
        $baseMassAction->expects($this->once())->method('setData');
        $this->componentMassActionMock->expects($this->once())->method('getMassActionsConfig')->willReturn([]);

        $this->massAction->afterPrepare($baseMassAction);
    }
}
