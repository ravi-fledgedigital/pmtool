<?php

namespace OnitsukaTigerKorea\MaskCustomerData\Block\Adminhtml\Report\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class MaskedCustomer extends AbstractRenderer
{
    public function __construct(
        Context $context,
        private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper,
        array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $name = $row->getData($this->getColumn()->getIndex());

        // Example masking: show only first letter of each part
        if (!empty($name) && $row->getStoreId() == 5) {
            return $this->helper->maskName($name);
        }
        return $name;
    }
}
