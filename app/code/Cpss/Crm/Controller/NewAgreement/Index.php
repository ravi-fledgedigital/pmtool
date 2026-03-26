<?php
namespace Cpss\Crm\Controller\NewAgreement;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Customer\Model\Logger;

class Index extends Action {

    protected $resultJsonFactory;
    protected $customerAccountManagement;
    protected $customerModel;
    protected $customerLogger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AccountManagementInterface $customerAccountManagement,
        Customer $customerModel,
        Logger $customerLogger
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerModel = $customerModel;
        $this->customerLogger = $customerLogger;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $resultJson = $this->resultJsonFactory->create();
        $params['agreed'] = false;
        if ($login = $params['login']) {
            try {
                $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                $customerModel = $this->customerModel->load($customer->getId());
                
                $lastLoggedInDate = $this->customerLogger->get($customer->getId())->getLastLoginAt();
                if ($customerModel->getIsAgreed()) {
                    $params['agreed'] = true;
                    return $resultJson->setData($params);
                }

            } catch (EmailNotConfirmedException | UserLockedException | AuthenticationException | LocalizedException | \Exception $e) {
                $params['agreed'] = true;
                return $resultJson->setData($params);
            }
        } 

        return $resultJson->setData($params);
        
    }
}