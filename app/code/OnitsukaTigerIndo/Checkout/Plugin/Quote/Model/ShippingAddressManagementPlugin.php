<?php
/** phpcs:ignoreFile */
namespace OnitsukaTigerIndo\Checkout\Plugin\Quote\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\ShippingAddressManagement;

/**
 * Class ShippingAddressManagementPlugin
 *
 * @package OnitsukaTigerIndo\Checkout\Plugin\Quote\Model
 */
class ShippingAddressManagementPlugin
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    public function beforeAssign(ShippingAddressManagement $subject, $cartId, AddressInterface $address)
    {
        $extAttributes = $address->getExtensionAttributes();
        $district = $extAttributes->getDistrict() ?? $address->getDistrict();
        $address->setDistrict($district);
    }
}
