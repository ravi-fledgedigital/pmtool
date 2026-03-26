<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Block\Order\Creditmemo;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Source\LinkType;
use Magento\Customer\Model\Context;

class ItemsPlugin
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
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    private $currentCreditmemo;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        ConfigProvider $configProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Registry $registry,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Block\Order\Creditmemo\Items $subject
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     */
    public function beforeGetPrintCreditmemoUrl(\Magento\Sales\Block\Order\Creditmemo\Items $subject, $creditmemo)
    {
        $this->currentCreditmemo = $creditmemo;
    }

    /**
     * @param \Magento\Sales\Block\Order\Creditmemo\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintCreditmemoUrl(\Magento\Sales\Block\Order\Creditmemo\Items $subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl(
                'custompdf/guest/creditmemo',
                ['creditmemo_id' => $this->currentCreditmemo->getId()]
            );
        }

        return $subject->getUrl(
            'custompdf/sales/creditmemo',
            ['creditmemo_id' => $this->currentCreditmemo->getId()]
        );
    }

    /**
     * @param \Magento\Sales\Block\Order\Creditmemo\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintAllCreditmemosUrl($subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl('custompdf/guest/creditmemo', ['order_id' => $order->getId()]);
        }

        return $subject->getUrl('custompdf/sales/creditmemo', ['order_id' => $order->getId()]);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    private function isEnabledLinkReplace($order)
    {
        $storeId = $order->getStoreId();
        $customerGroupId = $order->getCustomerGroupId();
        if (!$this->configProvider->isEnabled($storeId) ||
            !$this->templateRepository->getCreditmemoTemplateId($storeId, $customerGroupId)
        ) {
            return false;
        }
        $creditmemoLinkType = $this->configProvider->getCreditmemoLinkType($storeId);

        return $creditmemoLinkType == LinkType::TYPE_REPLACE;
    }
}
