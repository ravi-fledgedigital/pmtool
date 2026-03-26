<?php

namespace OnitsukaTigerCpss\Crm\Helper;

use Cpss\Crm\Helper\SftpHelper;
use Cpss\Pos\Helper\CreateCsv;
use Magento\Checkout\Helper\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Quote\Model\Quote\Validator\MinimumOrderAmount\ValidationMessage;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Helper Data Crm
 */
class HelperData extends AbstractHelper
{
    const CPSS_SFTP_EMAIL_CONFIG_EMAIL_ENABLE = 'sftp/cpss_email_config/enable';
    const CPSS_SFTP_EMAIL_CONFIG_RECEIVER_NAME = 'sftp/cpss_email_config/receiver_name';
    const CPSS_SFTP_EMAIL_CONFIG_RECEIVER_EMAIL = 'sftp/cpss_email_config/receiver_email';
    const CPSS_SFTP_EMAIL_CONFIG_RECEIVER_POS_SFTP_EMAIL_TEMPLATE = 'sftp/cpss_email_config/pos_sftp_email_template';
    const CPSS_SFTP_EMAIL_CONFIG_RECEIVER_CPSS_SFTP_EMAIL_TEMPLATE = 'sftp/cpss_email_config/cpss_sftp_email_template';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Session
     */
    protected $customerSession;
    protected $validationMessage;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Cart
     */
    private $cartHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    protected $temp_id;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        ValidationMessage $validationMessage,
        Cart $cartHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        protected \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        protected \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        private Sftp $sftp,
        private SftpHelper $sftpHelper,
        protected DirectoryList $dir
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->cartHelper = $cartHelper;
        $this->validationMessage = $validationMessage;
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Check module enable
     *
     * @return mixed
     */
    public function isEnableModule()
    {
        $isLoggedIn = $this->customerSession->isLoggedIn();
        return $this->scopeConfig->getValue(\Cpss\Crm\Helper\Data::CRM_ENABLED_PATH, ScopeInterface::SCOPE_STORE) && $isLoggedIn;
    }

    /**
     * Check module enable
     *
     * @return mixed
     */
    public function isCpssModuleEnable()
    {
        return $this->scopeConfig->getValue(\Cpss\Crm\Helper\Data::CRM_ENABLED_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check agreement status customer
     *
     * @return bool
     */
    public function checkAgreement()
    {
        if ($this->customerSession->getCustomer()->getData('is_agreed') == 1) {
            return true;
        }
        return false;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function checkAgreementById($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $customerAttributeData = $customer->__toArray();
        // return (isset($customerAttributeData['custom_attributes']['is_agreed']) && isset($customerAttributeData['custom_attributes']['is_agreed']['value'])) ? true : false;
        return (isset($customerAttributeData['custom_attributes']['is_agreed']) && isset($customerAttributeData['custom_attributes']['is_agreed']['value']) && $customerAttributeData['custom_attributes']['is_agreed']['value'] !== "0") ? true : false;
    }

    /**
     * @param $incrementId
     * @return false|\Magento\Sales\Model\Order
     */
    public function getOrderByIncrementId($incrementId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if ($order && $order->getId()) {
            return $order;
        }

        return false;
    }

    /**
     * Get minimum points value
     *
     * @return mixed
     */
    public function getMinimumPoints()
    {
        return $this->scopeConfig->getValue('crm/cpss_point/minimum_point', ScopeInterface::SCOPE_STORE, $this->getCurrentStoreId());
    }

    /**
     * Get minimum points value
     *
     * @return mixed
     */
    public function getPointMultiplyBy($storeId = '')
    {
        if (empty($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        $value = $this->scopeConfig->getValue('crm/cpss_point/point_multiple_by', ScopeInterface::SCOPE_STORE, $storeId);
        if (empty($value)) {
            $value = 100;
        }

        return $value;
    }

    /**
     * Get minimum points value
     *
     * @return mixed
     */
    public function getPointEarnedMultiplyBy($storeId = '')
    {
        if (empty($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        $value = $this->scopeConfig->getValue('crm/cpss_point/point_earned_multiple_by', ScopeInterface::SCOPE_STORE, $storeId);
        if (empty($value)) {
            $value = 100;
        }

        return $value;
    }

    /**
     * @return mixed
     */
    public function getPerXPointValue($storeId = '')
    {
        if (empty($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->scopeConfig->getValue('crm/cpss_point/per_x_point', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return mixed
     */
    public function getPerXRateValue($storeId = '')
    {
        if (empty($storeId)) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->scopeConfig->getValue('crm/cpss_point/per_x_rate', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return mixed
     */
    public function getMinimumOrderAmountForWorldPayPaymentMethod()
    {
        return $this->scopeConfig->getValue('crm/cpss_minimum_order_amount/world_pay', ScopeInterface::SCOPE_STORE, $this->getCurrentStoreId());
    }

    /**
     * @return mixed
     */
    public function getMinimumOrderAmountRazerPaymentMethod()
    {
        return $this->scopeConfig->getValue('crm/cpss_minimum_order_amount/razer', ScopeInterface::SCOPE_STORE, $this->getCurrentStoreId());
    }

    /**
     * @return mixed
     */
    public function getMinimumOrderAmountForOmisePaymentMethod()
    {
        return $this->scopeConfig->getValue('crm/cpss_minimum_order_amount/omise', ScopeInterface::SCOPE_STORE, $this->getCurrentStoreId());
    }

    /**
     * @return mixed
     */
    public function getMinimumOrderAmountForAdyenKakaoPayPaymentMethod()
    {
        return $this->scopeConfig->getValue('crm/cpss_minimum_order_amount/adyen_kakao_pay', ScopeInterface::SCOPE_STORE, $this->getCurrentStoreId());
    }

    /**
     * @return mixed
     */
    public function getShowStorePurchase()
    {
        return $this->scopeConfig->getValue('crm/general/show_store_purchase', ScopeInterface::SCOPE_STORE);
    }

    public function getSiteId($storeId = '', $websiteId = '')
    {
        if (!empty($storeId)) {
            return $this->scopeConfig->getValue('crm/general/site_id', ScopeInterface::SCOPE_STORE, $storeId);
        } elseif (!empty($websiteId)) {
            return $this->scopeConfig->getValue('crm/general/site_id', ScopeInterface::SCOPE_WEBSITE, $websiteId);
        }

        return $this->scopeConfig->getValue('crm/general/site_id', ScopeInterface::SCOPE_STORE);
    }

    public function getSitePassword($storeId = '', $websiteId = '')
    {
        if (!empty($storeId)) {
            return $this->scopeConfig->getValue('crm/general/site_password', ScopeInterface::SCOPE_STORE, $storeId);
        } elseif (!empty($websiteId)) {
            return $this->scopeConfig->getValue('crm/general/site_password', ScopeInterface::SCOPE_WEBSITE, $websiteId);
        }

        return $this->scopeConfig->getValue('crm/general/site_password', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get minimum order value
     * @return array
     */
    public function getMinimumOrder()
    {
        $isActiveMinimumOrder = $this->scopeConfig->getValue('sales/minimum_order/active', ScopeInterface::SCOPE_WEBSITE);
        $isValidMinimumOrder = false;
        $minimumOrderValue = $this->scopeConfig->getValue('sales/minimum_order/amount', ScopeInterface::SCOPE_WEBSITE);
        $minimumOrderMessage = $this->validationMessage->getMessage();
        if ($isActiveMinimumOrder && !$this->cartHelper->getQuote()->validateMinimumAmount()) {
            $isValidMinimumOrder = true;
        }

        return [
            'isActiveMinimumOrder' => (bool)$isActiveMinimumOrder,
            'minimumOrderValue' => empty($minimumOrderValue) ? 0 : $minimumOrderValue,
            'minimumOrderMessage' => $minimumOrderMessage,
            'isValidMinimumOrder' => $isValidMinimumOrder,
        ];
    }

    /**
     * @param $usedPointsOnCheckout
     * @return float|int
     */
    public function calculateUsedPointDiscount($usedPointsOnCheckout)
    {
        $pointRate = $this->getPerXRateValue();
        $perPoint = (!empty($this->getPerXPointValue())) ? $this->getPerXPointValue() : 100;

        if (empty($pointRate)) {
            $pointRate = 1;
        }
        $usedPoints = 0;
        if (!empty($usedPointsOnCheckout)) {
            $usedPoints = ($usedPointsOnCheckout * $pointRate) / $perPoint;
        }

        return $usedPoints;
    }

    /**
     * @return array
     */
    public function getStoreCodes()
    {
        $storeCode = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($store->getCode() == 'web_sg_en') {
                $storeCode[$store->getId()] = 'sg';
            } elseif ($store->getCode() == 'web_my_en') {
                $storeCode[$store->getId()] = 'my';
            } elseif ($store->getCode() == 'web_th_en') {
                $storeCode[$store->getId()] = 'th';
            } elseif ($store->getCode() == 'web_th_th') {
                $storeCode[$store->getId()] = 'th';
            } elseif ($store->getCode() == 'web_kr_ko') {
                $storeCode[$store->getId()] = 'kr';
            }
        }

        return $storeCode;
    }

    /**
     * Get store ids
     *
     * @return array
     */
    public function getStoreIds()
    {
        $storeCode = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($store->getCode() == 'web_sg_en') {
                $storeCode['sg'] = $store->getId();
            } elseif ($store->getCode() == 'web_my_en') {
                $storeCode['my'] = $store->getId();
            } elseif ($store->getCode() == 'web_th_en') {
                $storeCode['th'] = $store->getId();
            } elseif ($store->getCode() == 'web_th_th') {
                $storeCode['th'] = $store->getId();
            } elseif ($store->getCode() == 'web_kr_ko') {
                $storeCode['kr'] = $store->getId();
            }
        }

        return $storeCode;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Generate template
     *
     * @param $emailTemplate
     * @param $senderInfo
     * @param $receiverInfo
     * @param $areaCode
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateTemplate($emailTemplate, $senderInfo, $receiverInfo, $areaCode)
    {
        $template =  $this->transportBuilder->setTemplateIdentifier($this->temp_id)
            ->setTemplateOptions(
                [
                    'area' => $areaCode,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplate)
            ->setFrom($senderInfo)
            ->addTo($receiverInfo['receiver_email'], $receiverInfo['receiver_name']);
        return $this;
    }

    /**
     * Send email notification
     *
     * @param $emailTemplate
     * @param $senderInfo
     * @param $receiverInfo
     * @param $emailTemplateId
     * @param $areaCode
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendEmailNotification($emailTemplate, $senderInfo, $receiverInfo, $emailTemplateId, $areaCode)
    {
        $this->temp_id = $emailTemplateId;
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplate, $senderInfo, $receiverInfo, $areaCode);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * Send file exist email notifivation
     *
     * @param $emailTemplateName
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendFileExistEmailNotification($emailTemplateName = '')
    {
        if (!empty($emailTemplateName)) {
            $storeCodes = $this->getStoreCodes();
            $sCodePosArray = [];
            foreach ($storeCodes as $storeId => $code) {
                if ($this->isCpssEmailConfigEnable($storeId) && !in_array($code, $sCodePosArray)) {
                    $cpssCsvDir = $this->getPosCsvDir($code);
                    $files = scandir($cpssCsvDir);
                    $files = array_diff($files, ['.', '..']);
                    if (empty($files)) {
                        continue;
                    }
                    $sCodePosArray[] = $code;
                    $storeCode = strtoupper($code);
                    $senderInfo = [
                        'name' => $this->getSenderName('general', $storeId),
                        'email' => $this->getSenderEmail('general', $storeId),
                    ];
                    $receiverInfo = [
                        'receiver_name' => $this->getReceiverName($storeId),
                        'receiver_email' => $this->getReceiverEmail($storeId)
                    ];

                    $emailTemplate = [];
                    $emailTemplate['country'] = $storeCode;
                    if ($emailTemplateName == 'pos') {
                        $emailTemplateId = $this->getPosSftpReceiverTemplate($storeId);
                        $emailTemplate['subject'] = '[Alert for Point Service] ' . $storeCode . ' Files are remaining on POS SFTP server.';
                    } else {
                        $emailTemplateId = $this->getCpssSftpReceiverTemplate($storeId);
                        $emailTemplate['subject'] = '[Alert for Point Service] ' . $storeCode . ' Files are remaining on CPSS SFTP server.';
                    }

                    $this->sendEmailNotification($emailTemplate, $senderInfo, $receiverInfo, $emailTemplateId, \Magento\Framework\App\Area::AREA_FRONTEND);
                }
            }
        }
    }

    /**
     * Send cpss pending file SFTP email notifivation
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendCpssPendingFileSftpEmailNotification()
    {
        $storeCodes = $this->getStoreCodes();
        $sCodePosArray = [];
        foreach ($storeCodes as $storeId => $code) {
            if ($this->sftpHelper->cpssConnect($storeId)) {
                $sftpPath = $this->getConfigValue(\Cpss\Pos\Cron\CpssTransferFiles::CPSS_OUTBOUND_PATH, $storeId) . '/';
                $isDir = $this->sftp->cd($sftpPath); // Set Directory
                if ($isDir) {
                    $fileList = $this->sftp->rawls(); // Get File List inside current directory
                    $files = array_diff_key($fileList, array_flip(['.', '..']));
                    if (!empty($files)) {
                        if ($this->isCpssEmailConfigEnable($storeId) && !in_array($code, $sCodePosArray)) {
                            $sCodePosArray[] = $code;
                            $storeCode = strtoupper($code);
                            $senderInfo = [
                                'name' => $this->getSenderName('general', $storeId),
                                'email' => $this->getSenderEmail('general', $storeId),
                            ];
                            $receiverInfo = [
                                'receiver_name' => $this->getReceiverName($storeId),
                                'receiver_email' => $this->getReceiverEmail($storeId)
                            ];

                            $emailTemplate = [];
                            $emailTemplate['country'] = $storeCode;
                            $emailTemplateId = $this->getCpssSftpReceiverTemplate($storeId);
                            $emailTemplate['subject'] = '[Alert for Point Service] ' . $storeCode . ' Files are remaining on CPSS SFTP server.';

                            $this->sendEmailNotification($emailTemplate, $senderInfo, $receiverInfo, $emailTemplateId, \Magento\Framework\App\Area::AREA_FRONTEND);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get sender name.
     *
     * @param $key
     * @return string
     */
    public function getSenderName($key, $storeId): string
    {
        $path = 'trans_email/ident_' . $key . '/name';
        return $this->getConfigValue($path, $storeId);
    }

    /**
     * Get sender email
     *
     * @param $key
     * @return string
     */
    public function getSenderEmail($key, $storeId): string
    {
        $path = 'trans_email/ident_' . $key . '/email';
        return $this->getConfigValue($path, $storeId);
    }

    /**
     * Get config value
     *
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is cpss email config enable
     *
     * @return string
     */
    public function isCpssEmailConfigEnable($storeId): string
    {
        return $this->getConfigValue(self::CPSS_SFTP_EMAIL_CONFIG_EMAIL_ENABLE, $storeId);
    }

    /**
     * Get receiver name.
     *
     * @return string
     */
    public function getReceiverName($storeId): string
    {
        return $this->getConfigValue(self::CPSS_SFTP_EMAIL_CONFIG_RECEIVER_NAME, $storeId);
    }

    /**
     * Get receiver email
     *
     * @return string
     */
    public function getReceiverEmail($storeId): string
    {
        return $this->getConfigValue(self::CPSS_SFTP_EMAIL_CONFIG_RECEIVER_EMAIL, $storeId);
    }

    /**
     * Get pos sftp receiver template
     *
     * @return string
     */
    public function getPosSftpReceiverTemplate($storeId): string
    {
        return $this->getConfigValue(self::CPSS_SFTP_EMAIL_CONFIG_RECEIVER_POS_SFTP_EMAIL_TEMPLATE, $storeId);
    }

    /**
     * Get cpss sftp receiver template
     *
     * @return string
     */
    public function getCpssSftpReceiverTemplate($storeId): string
    {
        return $this->getConfigValue(self::CPSS_SFTP_EMAIL_CONFIG_RECEIVER_CPSS_SFTP_EMAIL_TEMPLATE, $storeId);
    }

    /**
     * Get store information
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore(): \Magento\Store\Api\Data\StoreInterface
    {
        return $this->storeManager->getStore();
    }

    public function getCpssCsvDir($code)
    {
        $varDir = $this->dir->getPath(DirectoryList::VAR_DIR);
        return $varDir . '/' . CreateCsv::CPSS_CSV_DIR . $code . '/';
    }

    public function getPosCsvDir($code)
    {
        $varDir = $this->dir->getPath(DirectoryList::VAR_DIR);
        return $varDir . '/crm/' . $code . '/PosSalesFile/';
    }
}
