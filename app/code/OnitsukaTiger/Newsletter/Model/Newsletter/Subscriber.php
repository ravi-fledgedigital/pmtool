<?php
declare(strict_types=1);
namespace OnitsukaTiger\Newsletter\Model\Newsletter;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Newsletter\Helper\Data;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Subscriber extends \Magento\Newsletter\Model\Subscriber {

     /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Newsletter\Helper\Data $newsletterData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime = null,
        CustomerInterfaceFactory $customerFactory = null,
        DataObjectHelper $dataObjectHelper = null
    )
    {
        $this->_localeDate = $localeDate;
        parent::__construct($context, $registry, $newsletterData, $scopeConfig, $transportBuilder, $storeManager, $customerSession, $customerRepository, $customerAccountManagement, $inlineTranslation, $resource, $resourceCollection, $data, $dateTime, $customerFactory, $dataObjectHelper);
    }

    /**
     * Sends out unsubscription email
     *
     * @return $this|Subscriber
     * @throws MailException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendUnsubscriptionEmail()
    {
        $this->sendEmail(self::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE, self::XML_PATH_UNSUBSCRIBE_EMAIL_IDENTITY);

        return $this;
    }

    /**
     * Send email about change status
     *
     * @param string $emailTemplatePath
     * @param string $emailIdentityPath
     * @param array $templateVars
     * @return void
     * @throws MailException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function sendEmail(string $emailTemplatePath, string $emailIdentityPath, array $templateVars = []): void
    {
        if ($this->getImportMode()) {
            return;
        }

        $template = $this->_scopeConfig->getValue($emailTemplatePath, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        $identity = $this->_scopeConfig->getValue($emailIdentityPath, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        if (!$template || !$identity) {
            return;
        }

        $templateVars += [
            'subscriber' => $this,
            'time' => $this->getChangeStatusAt()
        ];

        $this->inlineTranslation->suspend();
        $this->_transportBuilder->setTemplateIdentifier(
            $template

        )->setTemplateOptions(
            [
                'area' => Area::AREA_FRONTEND,
                'store' => $this->getStoreId(),
            ]
        )->setTemplateVars(
            $templateVars
        )->setFrom(
            $identity
        )->addTo(
            $this->getEmail(),
            $this->getName()
        );
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }


    public function getChangeStatusAt(): string
    {
        return $this->_localeDate->date(parent::getChangeStatusAt())->format('Y-m-d');
    }
}
