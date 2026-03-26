<?php

namespace OnitsukaTigerCpss\Customer\Block;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\View\Element\Template;

class AppRegisterRedirect extends Template
{
    /**
     * @var RedirectInterface
     */
    protected $redirect;
    protected $session;
    protected $crmHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session                  $customerSession,
        \OnitsukaTigerCpss\Crm\Helper\Data               $crmHelper,
        RedirectInterface                                $redirect,
        array                                            $data = []
    ) {
        $this->session = $customerSession;
        $this->crmHelper = $crmHelper;
        $this->redirect = $redirect;
        parent::__construct($context, $data);
    }
    /**
     * Validate temporary registration after register account
     * @return boolean
     */
    public function isRedirectAppLogin()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Login App Register Session Data: ' . $this->session->getIsRedirectAppLogin());

        $refererUrl = $this->redirect->getRefererUrl();
        $isRedirectAppLogin = $this->session->getIsRedirectAppLogin();

        if ($this->session->getNotRegisterRedirectToApp()) {
            $logger->info('===Is redirect to app login false===');
            return false;
        }

        return   !($this->session->isLoggedIn())
            && strpos($refererUrl, "customer/account/create")
            && $isRedirectAppLogin;
    }

    public function setNotRegisterRedirectToApp()
    {
        $this->session->setNotRegisterRedirectToApp(true);
    }

    /**
     * Redirect after temporary registration
     * @return string
     */
    public function getUrlRedirectToApp()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('New Customer ID: ' . $this->session->getCustomer()->getId());
        $logger->info('App Redirect URL: ' . $this->crmHelper->getAppRedirectUrl($this->session->getCustomer()->getId()));

        return $this->crmHelper->getAppRedirectUrl($this->session->getCustomer()->getId());
    }
}
