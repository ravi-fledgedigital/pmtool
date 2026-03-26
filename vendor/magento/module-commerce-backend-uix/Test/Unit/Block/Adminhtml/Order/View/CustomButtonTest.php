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

namespace Magento\CommerceBackendUix\Test\Unit\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\CommerceBackendUix\Block\Adminhtml\Order\View\CustomButton;
use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Code\NameBuilder;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Custom button Unit Tests
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomButtonTest extends TestCase
{
    /**
     * @var CustomButton
     */
    private $customButton;

    /**
     * @var Config&MockObject
     */
    private $configMock;

    /**
     * @var Cache&MockObject
     */
    private $cacheMock;

    /**
     * @var LayoutInterface&MockObject
     */
    private $layoutMock;

    /**
     * @var UrlInterface&MockObject
     */
    private $urlMock;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private AuthorizationInterface $authorizationInterfaceMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)->getMockForAbstractClass();
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)->getMockForAbstractClass();
        $this->authorizationInterfaceMock =
            $this->getMockBuilder(AuthorizationInterface::class)->getMockForAbstractClass();
        $this->configMock = $this->createMock(Config::class);
        $this->cacheMock = $this->createMock(Cache::class);
        $context = $this->mockContext();

        $this->customButton = new CustomButton(
            $context,
            $this->createMock(Registry::class),
            $this->getMockBuilder(ConfigInterface::class)->getMockForAbstractClass(),
            $this->createMock(Reorder::class),
            $this->urlMock,
            $this->cacheMock,
            new AuthorizationValidator($this->configMock, $this->authorizationInterfaceMock)
        );

        $this->customButton->setLayout($this->layoutMock);
    }

    /**
     * Test addButtons admin ui sdk disabled
     *
     * @return void
     */
    public function testAddButtonsAdminUiSdkDisabled()
    {
        $this->configMock->method('isAdminUISDKEnabled')->willReturn(false);
        $this->cacheMock->expects($this->never())->method('getOrderViewButtons');

        $this->customButton->addButtons();
    }

    /**
     * Test addButtons admin ui sdk enabled no parent block
     *
     * @return void
     */
    public function testAddButtonsAdminUiSdkEnabledNoParentBlock()
    {
        $this->configMock->method('isAdminUISDKEnabled')->willReturn(true);
        $this->cacheMock->expects($this->never())->method('getOrderViewButtons');

        $this->customButton->addButtons();
    }

    /**
     * Test addButtons admin ui sdk enabled with parent block no button registered
     *
     * @return void
     */
    public function testAddButtonsAdminUiSdkEnabledWithParentBlockNoButtonRegistered()
    {
        $this->configMock->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->layoutMock->method('getParentName')->willReturn('parentName');
        $this->layoutMock->method('getBlock')->willReturn($this->customButton);

        $this->cacheMock->expects($this->once())->method('getOrderViewButtons');
        $this->urlMock->expects($this->never())->method('getUrl');

        $this->customButton->addButtons();
    }

    /**
     * Test addButtons admin ui sdk enabled with parent block and button registered no sort order no confirm message
     *
     * @return void
     */
    public function testAddButtonsAdminUiSdkEnabledWithParentBlockAndButtonRegisteredNoSortOrderNoConfirmMessage()
    {
        $this->configMock->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->layoutMock->method('getParentName')->willReturn('parentName');
        $this->layoutMock->method('getBlock')->willReturn($this->customButton);

        $this->cacheMock
            ->expects($this->once())
            ->method('getOrderViewButtons')
            ->willReturn(
                [
                    [
                        'buttonId' => 'bId',
                        'extensionId' => 'eId',
                        'label' => 'button label',
                        'class' => 'button class',
                        'level' => 1
                    ]
                ]
            );

        $this->urlMock->expects($this->once())->method('getUrl');

        $this->customButton->addButtons();
    }

    /**
     * Test addButtons admin ui sdk enabled with parent block and button registered and sort order and confirm message
     *
     * @return void
     */
    public function testAddButtonsAdminUiSdkEnabledWithParentBlockAndButtonRegisteredAndSortOrderAndConfirmMessage()
    {
        $this->configMock->method('isAdminUISDKEnabled')->willReturn(true);
        $this->authorizationInterfaceMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->layoutMock->method('getParentName')->willReturn('parentName');
        $this->layoutMock->method('getBlock')->willReturn($this->customButton);

        $this->cacheMock
            ->expects($this->once())
            ->method('getOrderViewButtons')
            ->willReturn(
                [
                    [
                        'buttonId' => 'bId',
                        'extensionId' => 'eId',
                        'label' => 'button label',
                        'class' => 'button class',
                        'confirm' => [
                            'message' => 'are you sure?'
                        ],
                        'level' => 1,
                        'sortOrder' => 100
                    ]
                ]
            );

        $this->urlMock->expects($this->once())->method('getUrl');

        $this->customButton->addButtons();
    }

    /**
     * Mock context for testing
     *
     * @return Context|(Context&object&MockObject)|(Context&MockObject)|(object&MockObject)|MockObject
     */
    private function mockContext()
    {
        $context = $this->createMock(Context::class);
        $buttonListMock = $this->createMock(ButtonList::class);
        $nameBuilderMock = $this->createMock(NameBuilder::class);
        $requestMock = $this->getMockBuilder(RequestInterface::class)->getMockForAbstractClass();
        $toolbarMock = $this->getMockBuilder(ToolbarInterface::class)->getMockForAbstractClass();

        $context->method('getRequest')->willReturn($requestMock);
        $context->method('getUrlBuilder')->willReturn($this->urlMock);
        $context->method('getButtonList')->willReturn($buttonListMock);
        $context->method('getNameBuilder')->willReturn($nameBuilderMock);
        $context->method('getButtonToolbar')->willReturn($toolbarMock);

        $requestMock->method('getParam')->willReturn('');
        $buttonListMock->method('add');
        $this->urlMock->method('getUrl')->willReturn('');

        return $context;
    }
}
