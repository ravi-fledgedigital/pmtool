<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Controller\Account;

use Cpss\Crm\Plugin\Customer\CpssCreateAccount;
use OnitsukaTigerCpss\Crm\Helper\CrmData;
use OnitsukaTigerCpss\Crm\Helper\HelperData;

class Index extends CpssCreateAccount
{
    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    public function __construct(
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Magento\Customer\Model\Session $customerSession,
        CrmData $crmHelperData,
        \Cpss\Crm\Helper\Customer $helperCustomer,
        \Magento\Framework\Message\ManagerInterface $message,
        HelperData $helperData,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->helper = $helperData;
        $this->session = $customerSession;
        parent::__construct($cpssApiRequest, $customerSession, $crmHelperData, $helperCustomer, $message, $urlBuilder);
    }

    /**
     * @param \Magento\Customer\Controller\Account\Confirm $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(\Magento\Customer\Controller\Account\Index $subject, $result)
    {
        if ($this->helper->isEnableModule() && $this->customerSession->isLoggedIn()) {
            $this->createAccount(); // disable current version
        }
        return $result;
    }
}
