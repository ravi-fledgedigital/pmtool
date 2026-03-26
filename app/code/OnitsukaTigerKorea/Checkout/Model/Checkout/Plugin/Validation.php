<?php
/**
 * Validation
 */

namespace OnitsukaTigerKorea\Checkout\Model\Checkout\Plugin;

use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter;
use Magento\Customer\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTigerKorea\Checkout\Helper\Data;

class Validation extends \Magento\CheckoutAgreements\Model\Checkout\Plugin\Validation
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfiguration;

    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    private $agreementsValidator;

    /**
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface
     */
    private $checkoutAgreementsList;

    /**
     * @var \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter
     */
    private $activeStoreAgreementsFilter;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var Session
     */
    protected $customerSession;


    /**
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementsValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList
     * @param ActiveStoreAgreementsFilter $activeStoreAgreementsFilter
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementsValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList,
        ActiveStoreAgreementsFilter $activeStoreAgreementsFilter,
        CartRepositoryInterface $quoteRepository,
        Data $dataHelper,
        Session $customerSession
    ) {
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
        $this->agreementsValidator = $agreementsValidator;
        $this->scopeConfiguration = $scopeConfiguration;
        $this->checkoutAgreementsList = $checkoutAgreementsList;
        $this->activeStoreAgreementsFilter = $activeStoreAgreementsFilter;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($agreementsValidator, $scopeConfiguration, $checkoutAgreementsList, $activeStoreAgreementsFilter, $quoteRepository);
    }
    /**
     * Validates agreements before save payment information and  order placing.
     *
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
                                                                    $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->isAgreementEnabled()) {
            $this->validateAgreements($paymentMethod);
        }
    }

    /**
     * Verify if agreement validation needed.
     *
     * @return bool
     */
    private function isAgreementEnabled()
    {
        if ($this->dataHelper->getStoreCode() == 'web_kr_ko') {
            if (!$this->customerSession->isLoggedIn()) {
                return true;
            } else {
                return false;
            }
        } else {
            $isAgreementsEnabled = $this->scopeConfiguration->isSetFlag(
                AgreementsProvider::PATH_ENABLED,
                ScopeInterface::SCOPE_STORE
            );
            $agreementsList = $isAgreementsEnabled
                ? $this->checkoutAgreementsList->getList($this->activeStoreAgreementsFilter->buildSearchCriteria())
                : [];
            return (bool)($isAgreementsEnabled && count($agreementsList) > 0);
        }
    }
}