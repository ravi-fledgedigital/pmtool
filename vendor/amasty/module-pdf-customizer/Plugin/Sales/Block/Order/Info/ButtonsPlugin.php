<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Block\Order\Info;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Source\LinkType;
use Magento\Customer\Model\Context;

class ButtonsPlugin
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        ConfigProvider $configProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Block\Order\Info\Buttons $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintUrl(\Magento\Sales\Block\Order\Info\Buttons $subject, $result)
    {
        $order = $subject->getOrder();
        $storeId = $order->getStoreId();
        $customerGroupId = $order->getCustomerGroupId();
        if ($this->configProvider->isEnabled($storeId) &&
            $this->templateRepository->getOrderTemplateId($storeId, $customerGroupId) &&
            $this->configProvider->getOrderLinkType($storeId) == LinkType::TYPE_REPLACE
        ) {
            if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
                return $subject->getUrl('custompdf/guest/order', ['order_id' => $subject->getOrder()->getId()]);
            }
            return $subject->getUrl('custompdf/sales/order', ['order_id' => $subject->getOrder()->getId()]);
        }

        return $result;
    }
}
