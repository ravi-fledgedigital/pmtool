<?php
/**
 * ShippingMethodManagement
 */

namespace OnitsukaTiger\Checkout\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResource;
use Magento\Store\Model\ScopeInterface;

class ShippingMethodManagement extends \Magento\Quote\Model\ShippingMethodManagement
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param ShippingMethodConverter $converter
     * @param AddressRepositoryInterface $addressRepository
     * @param TotalsCollector $totalsCollector
     * @param AddressInterfaceFactory|null $addressFactory
     * @param QuoteAddressResource|null $quoteAddressResource
     * @param CustomerSession|null $customerSession
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ShippingMethodConverter $converter,
        AddressRepositoryInterface $addressRepository,
        TotalsCollector $totalsCollector,
        AddressInterfaceFactory $addressFactory = null,
        QuoteAddressResource $quoteAddressResource = null,
        CustomerSession $customerSession = null,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($quoteRepository, $converter, $addressRepository, $totalsCollector, $addressFactory, $quoteAddressResource, $customerSession);
    }

    /**
     * @inheritDoc
     */
    public function get($cartId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        $defaultCountry = $this->scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_WEBSITES
        );
        if (empty($shippingAddress->getCountryId()) && $defaultCountry != 'KR') {
            $shippingAddress->setCountryId($defaultCountry)->save();
        }
        if (!$shippingAddress->getCountryId()) {
            throw new StateException(__('The shipping address is missing. Set the address and try again.'));
        }

        $shippingMethod = $shippingAddress->getShippingMethod();
        if (!$shippingMethod) {
            return null;
        }

        $shippingAddress->collectShippingRates();
        /** @var Rate $shippingRate */
        $shippingRate = $shippingAddress->getShippingRateByCode($shippingMethod);
        if (!$shippingRate) {
            return null;
        }
        return $this->converter->modelToDataObject($shippingRate, $quote->getQuoteCurrencyCode());
    }
}
