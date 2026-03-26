<?php
/**
 * AgreementsConfigProvider
 */

namespace OnitsukaTigerKorea\Checkout\Model;

use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerKorea\Checkout\Helper\Data;

class AgreementsConfigProvider extends \Magento\CheckoutAgreements\Model\AgreementsConfigProvider
{
    /**
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface|mixed
     */
    protected $checkoutAgreementsList;

    /**
     * @var ActiveStoreAgreementsFilter|mixed
     */
    protected $activeStoreAgreementsFilter;

    /**
     * Agreement config provider
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface $checkoutAgreementsRepository
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface|null $checkoutAgreementsList
     * @param ActiveStoreAgreementsFilter|null $activeStoreAgreementsFilter
     * @param Data $dataHelper
     * @param Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsRepositoryInterface $checkoutAgreementsRepository,
        \Magento\Framework\Escaper $escaper,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList = null,
        ActiveStoreAgreementsFilter $activeStoreAgreementsFilter = null,
        Data $dataHelper,
        Session $customerSession
    ) {
        $this->scopeConfiguration = $scopeConfiguration;
        $this->checkoutAgreementsRepository = $checkoutAgreementsRepository;
        $this->escaper = $escaper;
        $this->checkoutAgreementsList = $checkoutAgreementsList ?: ObjectManager::getInstance()->get(
            \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface::class
        );
        $this->activeStoreAgreementsFilter = $activeStoreAgreementsFilter ?: ObjectManager::getInstance()->get(
            ActiveStoreAgreementsFilter::class
        );
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
        parent::__construct($scopeConfiguration, $checkoutAgreementsRepository, $escaper, $checkoutAgreementsList, $activeStoreAgreementsFilter);
    }

    /**
     * Returns agreements config.
     *
     * @return array
     */
    protected function getAgreementsConfig()
    {
        $agreementConfiguration = [];
        $isAgreementsEnabled = $this->scopeConfiguration->isSetFlag(
            AgreementsProvider::PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $agreementsList = $this->checkoutAgreementsList->getList(
            $this->activeStoreAgreementsFilter->buildSearchCriteria()
        );
        $agreementConfiguration['isEnabled'] = (bool)($isAgreementsEnabled && count($agreementsList) > 0);

        if ($this->dataHelper->getStoreCode() == 'web_kr_ko' && !$this->customerSession->isLoggedIn()) {
            $agreementConfiguration['isEnabled'] = true;
        } elseif ($this->dataHelper->getStoreCode() == 'web_kr_ko' && $this->customerSession->isLoggedIn()) {
            $agreementConfiguration['isEnabled'] = false;
        }
        foreach ($agreementsList as $agreement) {
            $agreementConfiguration['agreements'][] = [
                'content' => $agreement->getIsHtml()
                    ? $agreement->getContent()
                    : nl2br($this->escaper->escapeHtml($agreement->getContent())),
                'checkboxText' => $this->escaper->escapeHtml($agreement->getCheckboxText()),
                'mode' => $agreement->getMode(),
                'agreementId' => $agreement->getAgreementId(),
                'contentHeight' => $agreement->getContentHeight()
            ];
        }

        return $agreementConfiguration;
    }
}