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

namespace Magento\AdminUiSdkCustomFees\Test\Unit\Ui\Component\Listing\Column;

use Magento\AdminUiSdkCustomFees\Api\CustomFeesRepositoryInterface;
use Magento\AdminUiSdkCustomFees\Ui\Component\Listing\Column\CustomColumn;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Custom column test
 */
class CustomColumnTest extends TestCase
{
    /**
     * @var CustomColumn
     */
    private $customColumn;

    /**
     * @var ContextInterface|MockObject
     */
    private ContextInterface $contextMock;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var CustomFeesRepositoryInterface|MockObject
     */
    private $customFeesRepositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->customFeesRepositoryMock = $this->createMock(CustomFeesRepositoryInterface::class);
        $priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)->getMockForAbstractClass();

        $this->customColumn = new CustomColumn(
            $this->contextMock,
            $this->uiComponentFactoryMock ,
            $this->scopeConfigMock,
            $this->customFeesRepositoryMock,
            $priceCurrencyMock
        );
    }

    /**
     * Test prepare data source when namespace is wrong
     *
     * @return void
     */
    public function testPrepareDataSourceWrongNamespace(): void
    {
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn('wrong_namespace');

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => '1',
                        'custom_fee' => 10
                    ]
                ]
            ]
        ];

        $this->customColumn->prepareDataSource($dataSource);
    }

    /**
     * Test prepare data source for invoice grid
     *
     * @dataProvider namespaceProviderForInvoiceGrid
     * @param string $namespace
     * @return void
     */
    public function testPrepareDataSourceWhenItsInvoiceGrid($namespace): void
    {
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn($namespace);

        $this->customFeesRepositoryMock->expects($this->once())
            ->method('getByInvoiceId')
            ->willReturn([]);

        $config = [
            'attribute_code' => 'custom_fee'
        ];

        $this->customColumn->setData('config', $config);

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => '1',
                        'custom_fee' => 10
                    ]
                ]
            ]
        ];

        $this->customColumn->prepareDataSource($dataSource);
    }

    /**
     * Test prepare data source for credit memo grid
     *
     * @dataProvider namespaceProviderForCreditMemoGrid
     * @param string $namespace
     * @return void
     */
    public function testPrepareDataSourceWhenItsCreditMemoGrid($namespace): void
    {
        $this->contextMock->expects($this->once())
            ->method('getNamespace')
            ->willReturn($namespace);

        $this->customFeesRepositoryMock->expects($this->once())
            ->method('getByCreditMemoId')
            ->willReturn([]);

        $config = [
            'attribute_code' => 'custom_fee'
        ];

        $this->customColumn->setData('config', $config);

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'entity_id' => '1',
                        'custom_fee' => 10
                    ]
                ]
            ]
        ];

        $this->customColumn->prepareDataSource($dataSource);
    }

    /**
     * Namespace provider for invoice grid
     *
     * @return array
     */
    public function namespaceProviderForInvoiceGrid(): array
    {
        return [
            ['sales_order_view_invoice_grid'],
            ['sales_order_invoice_grid']
        ];
    }

    /**
     * Namespace provider for credit memo grid
     *
     * @return array
     */
    public function namespaceProviderForCreditMemoGrid(): array
    {
        return [
            ['sales_order_view_creditmemo_grid'],
            ['sales_order_creditmemo_grid']
        ];
    }
}
