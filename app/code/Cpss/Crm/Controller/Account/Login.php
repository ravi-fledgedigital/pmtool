<?php
namespace Cpss\Crm\Controller\Account;

class Login extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $session;
    protected $crmHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Helper\Customer $crmHelper
    ) {
        $this->_pageFactory = $pageFactory;
        $this->session = $customerSession;
        $this->crmHelper = $crmHelper;
        return parent::__construct($context);
    }

    public function execute()
    {
        if ($this->session->isLoggedIn()) {

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/login.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Login Session Data: ' . $this->session->getIsRedirectAppLogin());

            if ($this->session->getIsRedirectAppLogin()) {
                $url = $this->crmHelper->getAppRedirectUrl($this->session->getCustomer()->getId());

                return $this->_redirect($url);
            }

            $this->_redirect('customer/account');
        }

        return $this->_pageFactory->create();
    }
}
