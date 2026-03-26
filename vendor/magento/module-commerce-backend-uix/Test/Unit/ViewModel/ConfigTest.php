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

namespace Magento\CommerceBackendUix\Test\Unit\ViewModel;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config as ModelConfig;
use Magento\CommerceBackendUix\ViewModel\Config;
use Magento\CommerceBackendUix\ViewModel\OrderViewButtonConfig;
use Magento\CommerceBackendUix\ViewModel\MassAction\CustomerMassActionConfig;
use Magento\CommerceBackendUix\ViewModel\MassAction\MassActionConfig;
use Magento\CommerceBackendUix\ViewModel\MassAction\MassActionConfigFactory;
use Magento\CommerceBackendUix\ViewModel\MassAction\MassActions;
use Magento\CommerceBackendUix\ViewModel\MassAction\OrderMassActionConfig;
use Magento\CommerceBackendUix\ViewModel\MassAction\ProductMassActionConfig;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Config ViewModel
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigTest extends TestCase
{
    /**
     * @var \Magento\CommerceBackendUix\ViewModel\Config
     */
    private $viewModel;

    /**
     * @var RequestInterface&MockObject|MockObject
     */
    private $requestMock;

    /**
     * @var UrlInterface&MockObject|MockObject
     */
    private $backendUrlMock;

    /**
     * @var Filter&MockObject|MockObject
     */
    private $filterMock;

    /**
     * @var ProductCollectionFactory&MockObject|MockObject
     */
    private $productCollectionFactoryMock;

    /**
     * @var CustomerCollectionFactory&MockObject|MockObject
     */
    private $customerCollectionFactoryMock;

    /**
     * @var OrderCollectionFactory&MockObject|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var Template&MockObject|MockObject
     */
    private $templateMock;

    /**
     * @var ModelConfig&MockObject|MockObject
     */
    private $modelConfigMock;

    /**
     * @var Cache&MockObject
     */
    private $cacheMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)->getMockForAbstractClass();
        $this->backendUrlMock = $this->getMockBuilder(UrlInterface::class)->getMockForAbstractClass();
        $serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMockForAbstractClass();
        $this->filterMock = $this->createMock(Filter::class);
        $this->templateMock = $this->createMock(Template::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->customerCollectionFactoryMock = $this->createMock(CustomerCollectionFactory::class);
        $this->orderCollectionFactoryMock = $this->createMock(OrderCollectionFactory::class);
        $escaperMock = $this->createMock(Escaper::class);
        $escaperMock->expects($this->any())->method('escapeUrl')->willReturnArgument(0);
        $this->modelConfigMock = $this->createMock(ModelConfig::class);
        $this->cacheMock = $this->createMock(Cache::class);
        $this->viewModel = new Config(
            $this->requestMock,
            $escaperMock,
            $this->backendUrlMock,
            $serializerMock,
            $this->cacheMock,
            new MassActionConfig(
                $this->requestMock,
                $this->templateMock,
                $this->modelConfigMock,
                new MassActionConfigFactory([
                    'product' => new ProductMassActionConfig(
                        $escaperMock,
                        $this->backendUrlMock,
                        $this->productCollectionFactoryMock,
                        $this->filterMock,
                        $this->cacheMock
                    ),
                    'order' => new OrderMassActionConfig(
                        $this->requestMock,
                        $escaperMock,
                        $this->backendUrlMock,
                        $this->orderCollectionFactoryMock,
                        $this->filterMock,
                        $this->cacheMock
                    ),
                    'customer' => new CustomerMassActionConfig(
                        $this->customerCollectionFactoryMock,
                        $escaperMock,
                        $this->backendUrlMock,
                        $this->filterMock,
                        $this->cacheMock
                    )
                ]),
                new MassActions([
                    [
                        "type" => "product",
                        "requestId" => "productActionId"
                    ],
                    [
                        "type" => "order",
                        "requestId" => "orderActionId"
                    ],
                    [
                        "type" => "customer",
                        "requestId" => "customerActionId"
                    ]
                ])
            ),
            new OrderViewButtonConfig(
                $this->requestMock,
                $escaperMock,
                $this->backendUrlMock,
                $this->cacheMock
            )
        );
    }

    /**
     * Test getConfig when cache is empty
     *
     * @return void
     */
    public function testGetConfigWhenOpeningAdminPanelAndCacheIsEmpty(): void
    {
        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, null],
                ['productActionId', null, null],
                ['orderActionId', null, null],
                ['customerActionId', null, null],
                ['buttonId', null, null],
            ]);

        $this->assertEquals(
            [
                'extensions' => []
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Test getConfig when cache is not empty
     *
     * @return void
     */
    public function testGetConfigWhenOpeningAdminPanelAndCacheNotEmpty(): void
    {
        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, null],
                ['productActionId', null, null],
                ['orderActionId', null, null],
                ['customerActionId', null, null],
                ['buttonId', null, null],
            ]);

        $this->assertEquals(
            [
                'extensions' => []
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Test getConfig when accessing menu page
     *
     * @return void
     */
    public function testGetConfigWhenAccessingMenuPage(): void
    {
        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, 'test-extension-id'],
                ['productActionId', null, null],
                ['orderActionId', null, null],
                ['customerActionId', null, null],
                ['buttonId', null, null],
            ]);

        $this->assertEquals(
            [
                'extensions' => [],
                'selectedExtensionId' => 'test-extension-id'
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Test getConfig when selecting product mass action
     *
     * @return void
     */
    public function testGetConfigWhenSelectingProductMassAction(): void
    {
        $this->mockCommerceDataConfig();

        $this->backendUrlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminuisdk/redirect/redirect')
            ->willReturnArgument(0);

        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, 'test-extension-id'],
                ['productActionId', null, 'test-product-action-id'],
                ['orderActionId', null, null],
                ['customerActionId', null, null],
                ['buttonId', null, null],
            ]);

        $this->cacheMock->expects($this->once())
            ->method('getMassAction')
            ->with('product_listing', 'test-product-action-id')
            ->willReturn([
                'path' => 'test-product-action-path',
                'title' => 'test-product-action-title'
            ]);

        $this->assertEquals(
            [
                'extensions' => [],
                'selectedExtensionId' => 'test-extension-id',
                'commerce' => [
                    'baseUrl' => 'https://commerce-base-url',
                    'clientId' => 'testClientId'
                ],
                'massAction' => [
                    'actionId' => 'test-product-action-id',
                    'redirectUrl' => 'adminuisdk/redirect/redirect',
                    'actionUrlPath' => 'test-product-action-path',
                    'pageTitle' => 'test-product-action-title'
                ]
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Test getConfig when selecting order mass action
     *
     * @return void
     */
    public function testGetConfigWhenSelectingOrderMassAction(): void
    {
        $this->mockCommerceDataConfig();

        $this->backendUrlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminuisdk/redirect/redirect')
            ->willReturnArgument(0);

        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, 'test-extension-id'],
                ['productActionId', null, null],
                ['orderActionId', null, 'test-order-action-id'],
                ['customerActionId', null, null],
                ['buttonId', null, null],
            ]);

        $this->cacheMock->expects($this->once())
            ->method('getMassAction')
            ->with('sales_order_grid', 'test-order-action-id')
            ->willReturn([
                'path' => 'test-order-action-path',
                'title' => 'test-order-action-title'
            ]);

        $this->assertEquals(
            [
                'extensions' => [],
                'selectedExtensionId' => 'test-extension-id',
                'commerce' => [
                    'baseUrl' => 'https://commerce-base-url',
                    'clientId' => 'testClientId'
                ],
                'massAction' => [
                    'actionId' => 'test-order-action-id',
                    'redirectUrl' => 'adminuisdk/redirect/redirect',
                    'actionUrlPath' => 'test-order-action-path',
                    'pageTitle' => 'test-order-action-title'
                ]
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Test getConfig when selecting customer mass action
     *
     * @return void
     */
    public function testGetConfigWhenSelectingCustomerMassAction(): void
    {
        $this->mockCommerceDataConfig();

        $this->backendUrlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminuisdk/redirect/redirect')
            ->willReturnArgument(0);

        $this->requestMock->expects($this->exactly(5))
            ->method('getParam')
            ->willReturnMap([
                ['extensionId', null, 'test-extension-id'],
                ['productActionId', null, null],
                ['orderActionId', null, null],
                ['customerActionId', null, 'test-customer-action-id'],
                ['buttonId', null, null],
            ]);

        $this->cacheMock->expects($this->once())
            ->method('getMassAction')
            ->with('customer_listing', 'test-customer-action-id')
            ->willReturn([
                'path' => 'test-customer-action-path',
                'title' => 'test-customer-action-title'
            ]);

        $this->assertEquals(
            [
                'extensions' => [],
                'selectedExtensionId' => 'test-extension-id',
                'commerce' => [
                    'baseUrl' => 'https://commerce-base-url',
                    'clientId' => 'testClientId'
                ],
                'massAction' => [
                    'actionId' => 'test-customer-action-id',
                    'redirectUrl' => 'adminuisdk/redirect/redirect',
                    'actionUrlPath' => 'test-customer-action-path',
                    'pageTitle' => 'test-customer-action-title'
                ]
            ],
            $this->viewModel->getConfig()
        );
    }

    /**
     * Mock Commerce Data Config (BaseURL, IMSToken, ClientId)
     *
     * @return void
     */
    private function mockCommerceDataConfig(): void
    {
        $this->templateMock
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://commerce-base-url');

        $this->modelConfigMock
            ->expects($this->once())
            ->method('getClientId')
            ->willReturn('testClientId');
    }
}
