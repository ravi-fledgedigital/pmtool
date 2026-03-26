<?php

namespace OnitsukaTigerCpss\Crm\Plugin\Controller\NewAgreement;

use Cpss\Crm\Controller\NewAgreement\Index as NewAgreement;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Logger;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Message\ManagerInterface;

class Index extends NewAgreement
{
    public const AGREED = 1;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ManagerInterface
     */
    protected $message;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AccountManagementInterface $customerAccountManagement
     * @param Customer $customerModel
     * @param Logger $customerLogger
     * @param Session $customerSession
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $message
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AccountManagementInterface $customerAccountManagement,
        Customer $customerModel,
        Logger $customerLogger,
        Session $customerSession,
        ResultFactory $resultFactory,
        ManagerInterface $message,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->message = $message;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $resultJsonFactory, $customerAccountManagement, $customerModel, $customerLogger);
    }

    /**
     * Add agreement for customer
     *
     * @return ResponseInterface|Json|Redirect|ResultInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('is_agreed')) {
            $email = $this->getRequest()->getParam('email');
            $website = $this->getRequest()->getParam('website_id');
            $customer = $this->customerRepository->get($email, $website);
            $customer->setCustomAttribute('is_agreed', self::AGREED);
            $this->customerRepository->save($customer);
            $this->message->addSuccessMessage(__('Membership agreement is successful. Please log in again.'));
        } else {
            $this->message->addErrorMessage(__('Please agree to the Membership Agreement'));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl('/customer/account/index');
        return $resultRedirect;
    }
}
