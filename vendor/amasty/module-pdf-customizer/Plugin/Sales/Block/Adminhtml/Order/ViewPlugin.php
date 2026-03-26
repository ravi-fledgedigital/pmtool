<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Block\Adminhtml\Order;

class ViewPlugin
{
    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->configProvider = $configProvider;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $subject
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSetLayout($subject, $layout)
    {
        if ($this->configProvider->isEnabled()) {
            $printUrl = $subject->getUrl('amasty_pdf/order/print', ['order_id' => $subject->getOrderId()]);
            $subject->addButton(
                'pdf_order',
                [
                    'label' => __('Print Order PDF'),
                    'class' => 'print',
                    'onclick' => 'setLocation(\'' . $printUrl . '\')'
                ]
            );
        }
    }
}
