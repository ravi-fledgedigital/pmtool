<?php

namespace OnitsukaTigerCpss\Customer\Block;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\View\Element\Template;

class AppConfirmRedirect extends Template
{
    /**
     * @var RedirectInterface
     */
    protected $redirect;
    protected $session;
    protected $crmHelper;
    protected $request;
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
        RequestInterface                                 $request,
        array                                            $data = []
    ) {
        $this->session = $customerSession;
        $this->crmHelper = $crmHelper;
        $this->redirect = $redirect;
        $this->request = $request;
        parent::__construct($context, $data);
    }
    /**
     * Validate full registration
     * @return boolean
     */
    public function isRedirectAppConfirm()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Login Confirm Redirect Session Data: ' . $this->session->getIsRedirectAppLogin());


        $isRedirectAppLogin = $this->session->getIsRedirectAppLogin();
        $registeredfromapp = $this->session->getRegisteredFromApp();

        $logger->info('Is Logged In: ' . $this->session->isLoggedIn());
        $logger->info('Registered From App: ' . $registeredfromapp);

        if ($this->session->getNotRedirectToApp()) {
            $logger->info('===Not to redirect app===');
            return false;
        }

        return (($this->session->isLoggedIn()) && $isRedirectAppLogin)
        || $registeredfromapp;
    }

    public function setNotRedirectToApp()
    {
        $this->session->setNotRedirectToApp(true);
    }

    /**
     * Redirect after full registration
     * @return string
     */
    public function getUrlRedirectToApp()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Login Customer ID: ' . $this->session->getCustomer()->getId());
        $logger->info('App Redirect URL Register: ' . $this->crmHelper->getAppRedirectUrlRegister($this->session->getCustomer()->getId()));

        return $this->crmHelper->getAppRedirectUrlRegister($this->session->getCustomer()->getId());
    }
}
