<?php

namespace OnitsukaTigerKorea\Customer\Controller\Customer;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerKorea\Customer\Helper\Data;
use Psr\Log\LoggerInterface;
use Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken\CollectionFactory;
use Seoulwebdesign\KakaoSync\Service\Kakao;

class Delete extends AbstractAccount
{
    const EMAIL_TEMPLATE = "korean_address/customer_account/email_template_delete_customer";
    //const EMAIL_COPY_TO_TEMPLATE = 'korean_address/customer_account/email_template_delete_customer_email_copy_to';

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var Session
     */
    protected Session $customerSession;

    /**
     * @var Registry
     */
    protected Registry $registry;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    protected $customer;

    protected $scopeConfig;

    protected $date;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var Kakao
     */
    private Kakao $kakaoService;
    private CollectionFactory $accessTokenCollectionFactory;
    private \Seoulwebdesign\KakaoSync\Model\AccessTokenRepository $accessTokenRepository;
    private Curl $curl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Delete constructor.
     *
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        Registry $registry,
        LoggerInterface $logger,
        Data $helper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TimezoneInterface $date,
        Kakao $kakaoService,
        \Seoulwebdesign\KakaoSync\Model\AccessTokenRepository $accessTokenRepository,
        \Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken\CollectionFactory $accessTokenCollectionFactory,
        Curl                $curl
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->kakaoService = $kakaoService;
        parent::__construct($context);
        $this->accessTokenCollectionFactory = $accessTokenCollectionFactory;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->curl = $curl;
    }

    /**
     * Retrieve cookie manager
     *
     * @return     PhpCookieManager
     * @deprecated
     */
    private function getCookieManager(): PhpCookieManager
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(
                PhpCookieManager::class
            );
        }

        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @return     CookieMetadataFactory
     * @deprecated
     */
    private function getCookieMetadataFactory(): CookieMetadataFactory
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(
                CookieMetadataFactory::class
            );
        }

        return $this->cookieMetadataFactory;
    }

    /**
     * @return Redirect|ResultInterface|void
     */
    public function execute()
    {
        if (
            !$this->helper->allowDeleteAccount() ||
            !$this->customerSession->isLoggedIn()
        ) {
            $this->_forward("noRoute");
            return;
        }

        $customerId = $this->customerSession->getCustomerId();
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $customer = $this->customerRepository->getById($customerId);

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/unlink_customer.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info("============ Unlink Customer Start ==================");
            $logger->info("Customer Id: " . $customerId);
            $websiteId = $customer->getWebsiteId();
            $logger->info("Website Id: " . $websiteId);

            if ($websiteId == 4) {
                try {
                    $logger->info("Inside the if");
                    $customerToken = $this->getByCustomerId($customerId);
                    $logger->info("Customer Token : " . $customerToken->getAccessToken());
                    if ($customerToken->getAccessToken()) {
                        $logger->info("Inside the if customer token");
                        $accessToken = $customerToken->getAccessToken();
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://kapi.kakao.com/v1/user/unlink',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
                                'Authorization: Bearer ' . $accessToken,
                            ),
                        ));

                        $response = curl_exec($curl);

                        $logger->info("Kakao Response: " . json_encode($response));
                        curl_close($curl);
                    } else {
                        $logger->info("Access token not found");
                    }
                } catch (\Exception $e) {
                    $logger->info("Error while unlinking Kakao account: " . $e->getMessage());
                }
            }
            $logger->info("============ Unlink Customer End ==================");

            $writer = new \Zend_Log_Writer_Stream(
                BP . "/var/log/delete_customer.log"
            );
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info("customerId");
            $logger->info(print_r($customerId, true));
            $email = $customer->getEmail();
            $logger->info("customer_Email");
            $logger->info(print_r($email, true));
            $customer_name = $customer->getFirstname();
            $this->registry->register("isSecureArea", true, true);
            $this->customerSession->logout();
            $this->customerRepository->deleteById($customerId);
            if ($this->getCookieManager()->getCookie("mage-cache-sessid")) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath("/");
                $this->getCookieManager()->deleteCookie(
                    "mage-cache-sessid",
                    $metadata
                );
            }

            $this->messageManager->addSuccessMessage(
                __("Your account has been deleted.")
            );
            $this->sendEmail($email, $customer_name);
            $this->sendEmailToCC($email, $customer_name);
            $resultRedirect->setPath("/");
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $resultRedirect->setPath("customer/account/login");
        }

        return $resultRedirect;
    }

    public function sendEmail($email, $customer_name)
    {
        $this->inlineTranslation->suspend();
        $error = false;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $now = $this->date->date()->format("Y-m-d");
        $writer = new \Zend_Log_Writer_Stream(
            BP . "/var/log/delete_customer.log"
        );
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("Date");
        $logger->info(print_r($now, true));
        $sender = $this->scopeConfig->getValue(
            "korean_address/customer_account/sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $logger->info("Email Sender");
        $logger->info(print_r($sender, true));
        $subject = $this->scopeConfig->getValue(
            "korean_address/customer_account/email_subject_delete_customer",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $logger->info("Email Subject");
        $logger->info(print_r($subject, true));
        $template = $this->scopeConfig->getValue(
            self::EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        );

        $logger->info("Email Template");
        $logger->info(print_r($template, true));

        $transport = $this->_transportBuilder
            ->setTemplateIdentifier($template)
            ->setTemplateOptions([
                "area" => \Magento\Framework\App\Area::AREA_FRONTEND,
                "store" => $this->storeManager->getStore()->getId(),
            ])
            ->setTemplateVars([
                "customer_name" => $customer_name,
                "subject" => $subject,
                "email" => $email,
                "date" => $now,
            ])
            ->setFrom($sender)
            ->addTo($email)
            ->getTransport();

        try {
            $transport->sendMessage();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * OTSK-390-Send Emails of Comma-separated in Korea after Account Deletion
     *
     * @var $userEmail
     * @var $customer_name
     * @return bool
     */
    public function sendEmailToCC($userEmail, $customer_name)
    {
        $this->inlineTranslation->suspend();
        $error = false;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $now = $this->date->date()->format("Y-m-d");

        $sender = $this->scopeConfig->getValue(
            "korean_address/customer_account/email_copy_to_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $emails = $this->scopeConfig->getValue(
            "korean_address/customer_account/delete_customer_email_copy_to",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $emails = !empty($emails) ? explode(",", $emails) : [];

        $subject = $this->scopeConfig->getValue(
            "korean_address/customer_account/email_subject_delete_customer_copy_to",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $templateIdentifier = $this->scopeConfig->getValue(
            "korean_address/customer_account/email_template_delete_customer_email_copy_to",
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
            $this->logger->info("Send email successfully");
            return true;
        }
        return false;
    }
    public function getByCustomerId($customerId)
    {
        $collection = $this->accessTokenCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

        $item = $collection->getFirstItem();
        if (!$item->getId()) {
            throw new NoSuchEntityException(__('No access token found for customer ID %1', $customerId));
        }

        return $item;
    }
}
