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

namespace Magento\CommerceBackendUix\Test\Unit\Plugin\Ui\Component\Listing;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\CommerceBackendUix\Plugin\Ui\Component\Listing\Columns;
use Magento\CommerceBackendUix\Ui\Component\Listing\Column\CustomColumn;
use Magento\Framework\AuthorizationInterface;
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
     * @var UiComponentFactory&MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var Cache&MockObject
     */
    private $cacheMock;

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
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->cacheMock = $this->createMock(Cache::class);
        $this->authorizationInterfaceMock =
            $this->getMockBuilder(AuthorizationInterface::class)->getMockForAbstractClass();
        $this->columns = new Columns(
            $this->uiComponentFactoryMock,
            $this->cacheMock,
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
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(false);
        $this->authorizationInterfaceMock->expects($this->never())->method('isAllowed')->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())->method('getNamespace');

        $baseColumns = $this->createMock(BaseColumns::class);
        $baseColumns->expects($this->never())->method('getContext');
        $baseColumns->expects($this->never())->method('addComponent');

        $this->columns->afterPrepare($baseColumns);
    }

    /**
     * Test afterPrepare admin ui sdk enabled
     *
     * @dataProvider dataProviderForTestAfterPrepareAdminUiSdkEnabled
     * @param string $uiGridType
     * @return void
     */
    public function testAfterPrepareAdminUiSdkEnabled(string $uiGridType)
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())->method('getNamespace')->willReturn($uiGridType);

        $baseColumns = $this->createMock(BaseColumns::class);
        $baseColumns->expects($this->exactly(2))->method('getContext')->willReturn($context);
        $baseColumns->expects($this->once())->method('addComponent');

        $this->cacheMock->expects($this->once())->method('getRegisteredColumns')->with($uiGridType)->willReturn(
            [
                'data' => [
                    'meshId' => 'meshId',
                    'apiKey' => 'apiKey'
                ],
                'properties' => [
                    [
                        'label' => 'First Column',
                        'columnId' => 'first_column',
                        'type' => 'string',
                        'align' => 'left'
                    ]
                ]
            ]
        );

        $customColumn = $this->createMock(CustomColumn::class);
        $this->uiComponentFactoryMock->expects($this->once())->method('create')->willReturn($customColumn);

        $this->columns->afterPrepare($baseColumns);
    }

    /**
     * Data provider for testAfterPrepareAdminUiSdkEnabled.
     *
     * @return array
     */
    public function dataProviderForTestAfterPrepareAdminUiSdkEnabled(): array
    {
        return [
            [UiGridType::SALES_ORDER_GRID],
            [UiGridType::PRODUCT_LISTING_GRID],
            [UiGridType::CUSTOMER_GRID]
        ];
    }

    /**
     * Test afterPrepare for a grid not supported
     *
     * @return void
     */
    public function testAfterPrepareGridNotSupported()
    {
        $this->configMock->expects($this->once())->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())->method('getNamespace')->willReturn('other_grid');

        $baseColumns = $this->createMock(BaseColumns::class);
        $baseColumns->expects($this->exactly(1))->method('getContext')->willReturn($context);
        $baseColumns->expects($this->never())->method('addComponent');

        $this->cacheMock->expects($this->once())->method('getRegisteredColumns')->with('other_grid');
        $this->uiComponentFactoryMock->expects($this->never())->method('create');

        $this->columns->afterPrepare($baseColumns);
    }
}
