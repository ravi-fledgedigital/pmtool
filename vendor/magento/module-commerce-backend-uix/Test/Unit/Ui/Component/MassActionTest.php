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

namespace Magento\CommerceBackendUix\Test\Unit\Ui\Component;

use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\CommerceBackendUix\Ui\Component\MassAction;
use Magento\Framework\UrlInterface;
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
     * @var UrlInterface&MockObject
     */
    private $urlBuilderMock;

    /**
     * @var Cache&MockObject
     */
    private $cacheMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)->getMockForAbstractClass();
        $this->cacheMock = $this->createMock(Cache::class);
        $this->massAction = new MassAction($this->urlBuilderMock, $this->cacheMock);
    }

    /**
     * Test getMassActionConfig with wrong grid type
     *
     * @return void
     */
    public function testGetMassActionConfigWithWrongGridType()
    {
        $this->assertEquals([], $this->massAction->getMassActionsConfig(''));
        $this->assertEquals([], $this->massAction->getMassActionsConfig('wrong_grid'));
    }

    /**
     * Test getMassActionConfig
     *
     * @param string $gridType
     * @param string $controller
     * @param string $label
     * @param string $url
     * @return void
     * @dataProvider getMassActionConfigDataProvider
     */
    public function testGetMassActionConfig(
        string $gridType,
        string $controller,
        string $label,
        string $url
    ): void {
        $this->cacheMock
            ->method('getMassActions')
            ->with($gridType)
            ->willReturn([
                [
                    'label' => $label,
                    'actionId' => 'action-id',
                    'extensionId' => 'extension-id'
                ]
            ]);

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                $this->equalTo(sprintf('adminuisdk/%s/massAction', $controller)),
                $this->equalTo([$controller . 'ActionId' => 'action-id', 'extensionId' => 'extension-id'])
            )
            ->willReturn($url);

        $this->assertEquals([
            [
                'label' => $label,
                'type' => 'action-id',
                'url' => $url
            ]
        ], $this->massAction->getMassActionsConfig($gridType));
    }

    /**
     * Test getMassActionConfig with confirm message
     *
     * @param string $gridType
     * @param string $controller
     * @param string $label
     * @param string $url
     * @return void
     * @dataProvider getMassActionConfigDataProvider
     */
    public function testGetMassActionConfigWithConfirmMessage(
        string $gridType,
        string $controller,
        string $label,
        string $url
    ): void {
        $this->cacheMock
            ->method('getMassActions')
            ->with($gridType)
            ->willReturn(
                [
                    [
                        'label' => $label,
                        'actionId' => 'action-id',
                        'extensionId' => 'extension-id',
                        'confirm' => [
                            'message' => 'Confirm message',
                            'title' => 'Confirm title'
                        ]
                    ]
                ]
            );

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with(
                $this->equalTo(sprintf('adminuisdk/%s/massAction', $controller)),
                $this->equalTo([$controller . 'ActionId' => 'action-id', 'extensionId' => 'extension-id'])
            )
            ->willReturn($url);

        $this->assertEquals(
            [
                [
                    'label' => $label,
                    'type' => 'action-id',
                    'url' => $url,
                    'confirm' => [
                        'title' => 'Confirm title',
                        'message' => 'Confirm message'
                    ]
                ]
            ],
            $this->massAction->getMassActionsConfig($gridType)
        );
    }

    /**
     * Test getMassActionConfig with two mass actions
     *
     * @param string $gridType
     * @param string $controller
     * @param string $label
     * @param string $url
     * @return void
     * @dataProvider getMassActionConfigDataProvider
     */
    public function testGetMassActionConfigWithTwoMassActions(
        string $gridType,
        string $controller,
        string $label,
        string $url
    ) {
        $this->cacheMock
            ->method('getMassActions')
            ->with($gridType)
            ->willReturn(
                [
                    [
                        'label' => $label . ' 1',
                        'actionId' => 'action-id-1',
                        'extensionId' => 'extension-id'
                    ],
                    [
                        'label' => $label . ' 2',
                        'actionId' => 'action-id-2',
                        'extensionId' => 'extension-id'
                    ]
                ]
            );

        $this->urlBuilderMock->expects($this->exactly(2))
            ->method('getUrl')
            ->willReturnCallback(function ($path, $params) use ($controller, $url) {
                if ($path === sprintf('adminuisdk/%s/massAction', $controller)) {
                    if ($params === [$controller . 'ActionId' => 'action-id-1', 'extensionId' => 'extension-id'] ||
                        $params === [$controller . 'ActionId' => 'action-id-2', 'extensionId' => 'extension-id']) {
                        return $url;
                    }
                }
                return null;
            });

        $this->assertEquals(
            [
                [
                    'label' => $label . ' 1',
                    'type' => 'action-id-1',
                    'url' => $url
                ],
                [
                    'label' => $label . ' 2',
                    'type' => 'action-id-2',
                    'url' => $url
                ]
            ],
            $this->massAction->getMassActionsConfig($gridType)
        );
    }

    /**
     * Data provider for tests
     *
     * @return array
     */
    public function getMassActionConfigDataProvider(): array
    {
        return [
            [
                UiGridType::PRODUCT_LISTING_GRID,
                'product',
                'Test Product Mass Action',
                'adminuisdk/product/massAction/productAction/action-id/extensionId/extension-id'
            ],
            [
                UiGridType::SALES_ORDER_GRID,
                'order',
                'Test Order Mass Action',
                'adminuisdk/order/massAction/orderAction/action-id/extensionId/extension-id'
            ],
            [
                UiGridType::CUSTOMER_GRID,
                'customer',
                'Test Customer Mass Action',
                'adminuisdk/customer/massAction/customerAction/action-id/extensionId/extension-id'
            ]
        ];
    }
}
