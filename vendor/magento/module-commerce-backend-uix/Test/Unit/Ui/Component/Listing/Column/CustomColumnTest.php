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

namespace Magento\CommerceBackendUix\Test\Unit\Ui\Component\Listing\Column;

use Magento\CommerceBackendUix\Model\Grid\ColumnsDataRetriever;
use Magento\CommerceBackendUix\Ui\Component\Listing\Column\CustomColumn;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Custom Column Unit Tests
 */
class CustomColumnTest extends TestCase
{
    /**
     * @var \Magento\CommerceBackendUix\Ui\Component\Listing\Column\CustomColumn;
     */
    private $customColumn;

    /**
     * @var ContextInterface&MockObject|MockObject
     */
    private $contextMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var ColumnsDataRetriever
     */
    private $dataRetrieverMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->dataRetrieverMock = $this->createMock(ColumnsDataRetriever::class);
        $objectManager = new ObjectManager($this);
        $this->customColumn = $objectManager->getObject(
            CustomColumn::class,
            [
                'context' => $this->contextMock,
                'scopeConfig' => $this->scopeConfigMock,
                'dataRetriever'=> $this->dataRetrieverMock
            ]
        );
    }

    /**
     * Test prepareDataSource when in Sales Order Grid
     *
     * @return void
     */
    public function testPrepareDataSourceWhenItsSalesOrderGrid()
    {
        $this->mockScopeConfig();
        $this->setCustomColumnClassData('first_column');
        $this->mockPrepareExternalData('sales_order_grid');
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'increment_id' => 000001,
                        'first_column' => ''
                    ],
                ],
            ],
        ];

        $this->contextMock
            ->method('getNamespace')
            ->willReturn('sales_order_grid');

        $result =  $this->customColumn->prepareDataSource($dataSource);

        $this->assertEquals($dataSource, $result);
    }

    /**
     * Test prepareDataSource when in Products Grid
     *
     * @return void
     */
    public function testPrepareDataSourceWhenItsProductsGrid()
    {
        $this->mockScopeConfig();
        $this->setCustomColumnClassData('first_column');
        $this->mockPrepareExternalData('product_listing');
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'sku' => 000002,
                        'first_column' => ''
                    ],
                ],
            ],
        ];

        $this->contextMock
            ->method('getNamespace')
            ->willReturn('product_listing');

        $result =  $this->customColumn->prepareDataSource($dataSource);

        $this->assertEquals($dataSource, $result);
    }

    /**
     * Mock scope config getValue method
     *
     * @return void
     */
    private function mockScopeConfig(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn('test_mesh_base_url');
    }

    /**
     * Set mock data to CustomColumn class
     *
     * @return void
     */
    private function setCustomColumnClassData($customColumnName): void
    {
        $config = [
            'meshId' => 'meshId12344',
            'apiKey' => 'apiKey123456',
            'attribute_code' => 'test_attribute_code',
            'data_type' => 'text',
        ];

        $this->customColumn->setData('config', $config);
        $this->customColumn->setData('name', $customColumnName);
    }

    /**
     * Mock DataRetriever external data
     *
     * @param string $namespace
     * @retun void
     */
    private function mockPrepareExternalData(string $namespace): void
    {
        $contextNamespace = 'order';
        $grid = 'orderGridColumns';
        if ($namespace === 'product_listing') {
            $contextNamespace = 'product';
            $grid = 'productGridColumns';
        }

        $externalData = [
            'data' => [
                $contextNamespace => [
                    $grid => [
                        $namespace === 'sales_order_grid' ? '00001' : '00002' => [
                            'first_column' => $namespace === 'sales_order_grid'? 'order_value1' : 'product_value1',
                        ],
                    ],
                ],
            ],
        ];

        $this->dataRetrieverMock->expects($this->once())
            ->method('getColumnData')
            ->willReturn($externalData);
    }
}
