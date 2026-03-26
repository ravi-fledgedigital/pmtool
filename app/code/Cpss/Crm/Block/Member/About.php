<?php
namespace Cpss\Crm\Block\Member;

use Magento\Framework\View\Element\Template\Context;

class About extends \Magento\Framework\View\Element\Template
{    
    /**
     * @param Context $context
     */

    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);
    }

    public function displayMemberServicePage()
    {
        return $this->_scopeConfig->getValue('crm/membership/editor_textarea');
    }
}