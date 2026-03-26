<?php
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Block\Adminhtml\Product;
use Magento\Framework\Registry;

class Restock extends \Magento\Backend\Block\Template
{

    protected $_coreRegistry = null;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getProduct()
    {
        return $this->_coreRegistry->registry('current_product');
    }
}
