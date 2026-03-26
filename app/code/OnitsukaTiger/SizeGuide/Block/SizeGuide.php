<?php

namespace OnitsukaTiger\SizeGuide\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class SizeGuide extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param LayoutInterface $layout
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        protected LayoutInterface $layout,
        \Magento\Framework\View\Element\Template\Context             $context,
        protected \Magento\Framework\Registry $registry,
        array                                                        $data = []
    ) {
        $this->_layout = $layout;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed|null
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return string
     */
    public function getSizeGuideContent()
    {
        $result ='';

        $sizeGuideValue = $this->scopeConfig->getValue(
            'sizechart/select_size_chart/product_group_blocks',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $currentProduct = $this->getCurrentProduct();
        if (is_string($sizeGuideValue) && !empty($sizeGuideValue)) {
            $decoded = json_decode($sizeGuideValue, true);
            if (is_array($decoded)) {
                foreach ($decoded as $group) {
                    if (in_array(str_replace(' ', '', strtolower($currentProduct->getAttributeText('product_group'))), $group['product_group'])) {
                        $result = $group['cms_block'];
                        break;
                    }
                }
            }
        }
        if (!empty($result)) {
            return $this->getHtmlContentOfSizeGuide($result);
        }
        return '';
    }

    /**
     * @param $blockId
     * @return string
     */
    public function getHtmlContentOfSizeGuide($blockId)
    {
        if ($blockId) {
            return $this->_layout->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($blockId)
                ->toHtml();
        }
        return '';
    }
}
