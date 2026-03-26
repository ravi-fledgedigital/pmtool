<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Controller\Manage;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Customers newsletter subscription save controller
 */
class Save extends \Magento\Newsletter\Controller\Manage implements HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var SubscriptionManagerInterface
     */
    private $subscriptionManager;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

     /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    protected $scopeConfig;

    protected $date;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CustomerRepository $customerRepository
     * @param SubscriptionManagerInterface $subscriptionManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TimezoneInterface $date,
        CustomerRepository $customerRepository,
        SubscriptionManagerInterface $subscriptionManager
    ) {
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->formKeyValidator = $formKeyValidator;
        $this->date = $date;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $customerSession);
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * Save newsletter subscription preference action
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('customer/account/');
        }

        $customerId = $this->_customerSession->getCustomerId();
        if ($customerId === null) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving your subscription.'));
        } else {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $storeId = (int)$this->storeManager->getStore()->getId();
                $customer->setStoreId($storeId);
                $isSubscribedState = $customer->getExtensionAttributes()->getIsSubscribed();
                $isSubscribedParam = (boolean)$this->getRequest()->getParam('is_subscribed', false);
                if ($isSubscribedParam !== $isSubscribedState) {
                    // No need to validate customer and customer address while saving subscription preferences
                    $this->setIgnoreValidationFlag($customer);
                    $this->customerRepository->save($customer);
                    if ($isSubscribedParam) {
                        $subscribeModel = $this->subscriptionManager->subscribeCustomer((int)$customerId, $storeId);
                        $subscribeStatus = (int)$subscribeModel->getStatus();
                        if ($subscribeStatus === Subscriber::STATUS_SUBSCRIBED) {
                            $this->messageManager->addSuccess(__('We have saved your subscription.'));
                        } else {
                            $this->messageManager->addSuccess(__('A confirmation request has been sent.'));
                        }
                    } else {
                        $this->subscriptionManager->unsubscribeCustomer((int)$customerId, $storeId);
                        $customer_name = $customer->getFirstname();
                        $email = $customer->getEmail();
                        $this->sendEmailToUnsubscribeCC($email, $customer_name);
                        $this->messageManager->addSuccess(__('We have removed your newsletter subscription.'));
                    }
                } else {
                    $this->messageManager->addSuccess(__('We have updated your subscription.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving your subscription.'));
            }
        }
        return $this->_redirect('customer/account/');
    }

    /**
     * Set ignore_validation_flag to skip unnecessary address and customer validation
     *
     * @param CustomerInterface $customer
     * @return void
     */
    private function setIgnoreValidationFlag(CustomerInterface $customer): void
    {
        $customer->setData('ignore_validation_flag', true);
    }

     /**
     * OTSK-390-Send Emails of Comma-separated in Korea after newsletter unsubscription
     *
     * @var $userEmail
     * @var $customer_name
     * @return bool
     */
    public function sendEmailToUnsubscribeCC($userEmail, $customer_name)
    {
        $this->inlineTranslation->suspend();
        $error = false;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $now = $this->date->date()->format("Y-m-d");
        
        $writer = new \Zend_Log_Writer_Stream(
            BP . "/var/log/newsletter_unsubscription.log"
        );
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $sender = $this->scopeConfig->getValue(
            "korean_address/newsletter_unsubscription/un_email_copy_to_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $logger->info("Email Sender");
        $logger->info(print_r($sender, true));

        $emails = $this->scopeConfig->getValue(
            "korean_address/newsletter_unsubscription/un_email_copy_to",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $emails = !empty($emails) ? explode(",", $emails) : [];

        $subject = $this->scopeConfig->getValue(
            "korean_address/newsletter_unsubscription/email_subject_un_copy_to",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $templateIdentifier = $this->scopeConfig->getValue(
            "korean_address/newsletter_unsubscription/email_template_un_email_copy_to",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $templateVars = [
            "customer_name" => $customer_name,
            "subject" => $subject,
            "email" => $userEmail,
            "date"=>$now,
        ];
        if (!empty($emails)) {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateIdentifier)
                ->setTemplateOptions([
                    "area" => \Magento\Framework\App\Area::AREA_FRONTEND,
                    "store" => $this->storeManager->getStore()->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($emails)
                ->getTransport();
            try {
                $transport->sendMessage();
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
            $this->inlineTranslation->resume();
            $logger->info("Send newsletter unsubscription email successfully");
            return true;
        }
        return false;
    }
}
