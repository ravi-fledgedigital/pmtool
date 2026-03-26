<?php

namespace OnitsukaTigerVn\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\LayoutInterface;

class ConfigProviderTerms implements ConfigProviderInterface
{
    /** @var LayoutInterface  */
    protected $_layout;
    protected $cmsBlock;

    public function __construct(LayoutInterface $layout, $blockId)
    {
        $this->_layout = $layout;
        $this->cmsBlock = $this->constructBlock($blockId);
    }

    /**
     * @param $blockId
     * @return mixed
     */
    public function constructBlock($blockId)
    {
        $block = $this->_layout->createBlock('Magento\Cms\Block\Block')
            ->setBlockId($blockId)
            ->toHtml();
        return $block;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'onepay_checkout_content_terms_vn' => $this->cmsBlock
        ];
    }
}
