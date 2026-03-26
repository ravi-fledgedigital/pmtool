<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Amasty\PDFCustom\Block\Adminhtml\Template;

class Preview extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magento\Framework\Filter\Input\MaliciousCode
     */
    protected $maliciousCode;

    /**
     * @var \Amasty\PDFCustom\Model\TemplateFactory
     */
    protected $templateFactory;

    /**
     * @var string
     */
    protected $profilerName = 'pdf_template_proccessing';

    /**
     * @var \Amasty\PDFCustom\Model\Template\PreviewSimpleDataProvider
     */
    protected $previewSimpleDataProvider;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Preview\MaliciousCode $maliciousCode,
        \Amasty\PDFCustom\Model\TemplateFactory $templateFactory,
        \Amasty\PDFCustom\Model\Template\PreviewSimpleDataProvider $previewSimpleDataProvider,
        \Magento\Framework\Escaper $escaper,
        array $data = []
    ) {
        $this->maliciousCode = $maliciousCode;
        $this->templateFactory = $templateFactory;
        $this->previewSimpleDataProvider = $previewSimpleDataProvider;
        $this->escaper = $escaper;

        parent::__construct($context, $data);
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $storeId = $this->getAnyStoreView()->getId();
        /** @var $template \Magento\Email\Model\Template */
        $template = $this->templateFactory->create();

        if ($id = (int)$this->getRequest()->getParam('id')) {
            $template->load($id);
        } else {
            $type = $this->getRequest()->getParam('type');
            $text = $this->getRequest()->getParam('text');
            $styles = $this->getRequest()->getParam('styles');
            if ((!$type || !$text || !$styles) && $this->getRequest()->getParam('template_id')) {
                $loadedTemplate = $this->templateFactory->create();
                $loadedTemplate->load($this->getRequest()->getParam('template_id'));
                $type = $loadedTemplate->getType();
                $text = $loadedTemplate->getTemplateText();
                $styles = $loadedTemplate->getTemplateStyles();
            }

            $template->setTemplateType($type);
            $template->setTemplateText($text);
            $template->setTemplateStyles($styles);
        }

        $template->setTemplateText($this->maliciousCode->filter($template->getTemplateText()));

        \Magento\Framework\Profiler::start($this->profilerName);

        $template->emulateDesign($storeId);
        $templateProcessed = $this->_appState->emulateAreaCode(
            \Magento\Email\Model\AbstractTemplate::DEFAULT_DESIGN_AREA,
            [$template, 'getProcessedTemplate'],
            [$this->previewSimpleDataProvider->getVariablesData()] //array in array because callback
        );
        $template->revertDesign();

        if ($template->isPlain()) {
            $templateProcessed = "<pre>" . $this->escaper->escapeHtml($templateProcessed) . "</pre>";
        }

        \Magento\Framework\Profiler::stop($this->profilerName);

        return $templateProcessed;
    }

    /**
     * Get either default or any store view
     *
     * @return \Magento\Store\Model\Store|null
     */
    protected function getAnyStoreView()
    {
        $store = $this->_storeManager->getDefaultStoreView();
        if ($store) {
            return $store;
        }
        foreach ($this->_storeManager->getStores() as $store) {
            return $store;
        }
        return null;
    }
}
