<?php

namespace OnitsukaTigerCpss\Crm\Block\Member;

use Cpss\Crm\Block\Member\About as CrmMemberAbout;
use Magento\Framework\View\Element\Template\Context;
use OnitsukaTigerCpss\Crm\Helper\CrmData;
use Magento\Store\Model\ScopeInterface;

/**
 * About member service
 */
class About extends CrmMemberAbout
{

    /**
     * @var CrmData
     */
    protected $crmData;

    /**
     * @param Context $context
     * @param CrmData $crmData
     */
    public function __construct(
        Context $context,
        CrmData $crmData
    ) {
        parent::__construct($context);
        $this->crmData = $crmData;
    }

    /**
     * Display Member Service Page
     *
     * @return mixed
     */
    public function displayMemberServicePage()
    {
        return $this->_scopeConfig->getValue('crm/membership/editor_textarea', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get form action URL for POST create member request
     *
     * @return string
     */
    public function getFormAction()
    {
        return '/customer/newagreement';
    }
}
