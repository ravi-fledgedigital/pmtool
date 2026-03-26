<?php
namespace Cpss\Pos\Block\Adminhtml\Email;

class PosContent extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function parseContent()
    {
        return $this->getData("pos_data");
    }
}
