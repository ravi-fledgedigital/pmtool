<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Block\Adminhtml\System\Config;

use Magento\PaypalOnBoarding\Model\Button\RequestCommand;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\PaypalOnBoarding\Model\MiddlemanService;
use PHPUnit\Framework\TestCase;

/**
 * Class contains tests for PayPal On-Boarding integration
 *
 * @magentoAppArea adminhtml
 */
class OnBoardingWizardTest extends TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var CurlFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientFactory;

    /**
     * @var OnBoardingWizard
     */
    private $onBoardingWizard;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->clientFactory = $this->getMockBuilder(CurlFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $middlemanService = $this->objectManager->create(MiddlemanService::class, [
            'requestButtonCommand' => $this->objectManager->create(RequestCommand::class, [
                'clientFactory' => $this->clientFactory
            ])
        ]);

        $this->onBoardingWizard = $this->objectManager->create(OnBoardingWizard::class, [
            'middlemanService' => $middlemanService
        ]);
    }

    /**
     * Check if OnBoardingWizard buttons contains links to PayPal
     */
    public function testOnBoardingWizardButton()
    {
        $liveButtonUrl = "https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $sandboxButtonUrl = "https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $middlemanResponse = json_encode(['liveButtonUrl' => $liveButtonUrl, 'sandboxButtonUrl' => $sandboxButtonUrl]);

        /** @var Curl|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'getStatus', 'getBody'])
            ->getMock();

        $this->clientFactory->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);

        $httpClient->expects(static::once())
            ->method('get')
            ->with($this->anything());
        $httpClient->expects(static::once())
            ->method('getStatus')
            ->willReturn(200);
        $httpClient->expects(static::exactly(2))
            ->method('getBody')
            ->willReturn($middlemanResponse);

        $html = $this->onBoardingWizard->toHtml();

        $this->assertStringContainsString($liveButtonUrl, $html);
        $this->assertStringContainsString($sandboxButtonUrl, $html);
    }
}
