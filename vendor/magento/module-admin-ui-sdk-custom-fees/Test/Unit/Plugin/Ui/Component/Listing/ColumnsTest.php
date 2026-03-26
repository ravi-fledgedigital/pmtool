<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdminUiSdkCustomFees\Test\Unit\Plugin\Ui\Component\Listing;

use Magento\AdminUiSdkCustomFees\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\AdminUiSdkCustomFees\Plugin\Ui\Component\Listing\Columns;
use Magento\AdminUiSdkCustomFees\Ui\Component\Listing\Column\CustomColumn;
use Magento\Framework\AuthorizationInterface;
use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns as BaseColumns;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Columns Unit Tests
 */
class ColumnsTest extends TestCase
{
    /**
     * @var Columns
     */
    private $columns;

    /**
     * @var Config&MockObject
     */
    private $configMock;


    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var Cache|MockObject
     */
    private $cacheMock;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private AuthorizationInterface $authorizationInterfaceMock;

    /**
     * @var BaseColumns|MockObject
     */
    private $baseColumnsMock;

    /** 
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->cacheMock = $this->createMock(Cache::class);
        $this->configMock = $this->createMock(Config::class);
        $this->authorizationInterfaceMock =
            $this->getMockBuilder(AuthorizationInterface::class)->getMockForAbstractClass();
        $this->baseColumnsMock = $this->createMock(BaseColumns::class);
        $this->contextMock = $this->createMock(ContextInterface::class);

        $this->columns = new Columns(
            $this->uiComponentFactoryMock,
            $this->cacheMock,
            new AuthorizationValidator($this->configMock, $this->authorizationInterfaceMock)
        );
    }

    /**
     * Test afterPrepare method when Admin UI SDK is disabled
     * @return void
     */
    public function testAfterPrepareAdminUiSdkDisabled(): void
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(false);
        $this->authorizationInterfaceMock->expects($this->never())->method('isAllowed')->willReturn(true);
        $this->contextMock->expects($this->never())->method('getNamespace');

        $this->baseColumnsMock->expects($this->never())->method('getContext');
        $this->baseColumnsMock->expects($this->never())->method('addComponent');

        $this->columns->afterPrepare($this->baseColumnsMock);
    }

    /**
     * Test afterPrepare method when Admin UI SDK is enabled
     * @dataProvider namespaceProvider
     * @param string $namespace
     * @return void
     */
    public function testAfterPrepareAdminUiSdkEnabled(string $namespace): void
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn($namespace);

        $this->baseColumnsMock->expects($this->exactly(2))
            ->method('getContext')
            ->willReturn($this->contextMock);

        $this->baseColumnsMock->expects($this->once())
            ->method('addComponent');

        $this->cacheMock->expects($this->once())
            ->method('getOrderCustomFees')
            ->willReturn([
                [
                    'id' => 'test_id',
                    'label' => 'test_label'
                ]
            ]);

        $customColumn = $this->createMock(CustomColumn::class);
        $this->uiComponentFactoryMock->expects($this->once())->method('create')->willReturn($customColumn);
        
        $this->columns->afterPrepare($this->baseColumnsMock);
    }

    /**
     * Test afterPrepare method when Admin UI SDK is enabled and custom fees are empty
     * @dataProvider namespaceProvider
     * @param string $namespace
     * @return void
     */
    public function testAfterPrepareEmptyCustomFees($namespace): void
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn($namespace);

        $this->baseColumnsMock->expects($this->once())
            ->method('getContext')
            ->willReturn($this->contextMock);

        $this->baseColumnsMock->expects($this->never())
            ->method('addComponent');

        $this->cacheMock->expects($this->once())
            ->method('getOrderCustomFees')
            ->willReturn([]);

        $this->columns->afterPrepare($this->baseColumnsMock);
    }

    /**
     * Test afterPrepare method when Admin UI SDK is enabled and namespace is wrong
     * @return void
     */
    public function testWrongNamespaceProvider(): void
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn('wrong_namespace');

        $this->baseColumnsMock->expects($this->once())
            ->method('getContext')
            ->willReturn($this->contextMock);

        $this->baseColumnsMock->expects($this->never())
            ->method('addComponent');

        $this->columns->afterPrepare($this->baseColumnsMock);
    }

    /**
     * Namespace provider for all eligible namespaces
     * @return array
     */
    public function namespaceProvider(): array
    {
        return [
            ['sales_order_view_invoice_grid'],
            ['sales_order_view_creditmemo_grid'],
            ['sales_order_invoice_grid'],
            ['sales_order_creditmemo_grid'],
        ];
    }
    
}
