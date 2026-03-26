<?php
/** phpcs:ignoreFile */
declare(strict_types=1);

namespace OnitsukaTigerIndo\Checkout\Plugin\Quote\Model\Quote;

use Magento\Quote\Model\Quote\Address as MageQuoteAddress;

/**
 * Class AddressPlugin
 *
 * @package OnitsukaTigerIndo\Checkout\Plugin\Quote\Model\Quote
 */
class AddressPlugin
{
    /**
     * @param MageQuoteAddress $subject
     * @param $result
     * @return mixed
     */
    public function afterExportCustomerAddress(MageQuoteAddress $subject, $result)
    {
        $result->setCustomAttribute('district', $subject->getDistrict());

        return $result;
    }
}
