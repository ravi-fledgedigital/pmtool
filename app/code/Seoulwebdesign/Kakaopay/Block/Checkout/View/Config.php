<?php

namespace Seoulwebdesign\Kakaopay\Block\Checkout\View;

use Magento\Catalog\Block\Product\Context;

class Config extends \Magento\Framework\View\Element\Template
{
    protected $_configHelper;

    public function __construct(
        Context $context,
        \Seoulwebdesign\Kakaopay\Helper\ConfigHelper $configHelper,
        array $data
    ) {
        $this->_configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    public function getConfigData()
    {
        return [
            'instructions' => $this->_configHelper->getInstructions()
        ];
    }
}
