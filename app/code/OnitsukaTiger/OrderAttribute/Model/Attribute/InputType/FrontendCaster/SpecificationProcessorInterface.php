<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\FrontendCaster;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;

/**
 * Service Provider Interface - SPI
 */
interface SpecificationProcessorInterface
{
    /**
     * @param string[] $element
     * @param CheckoutAttributeInterface $attribute
     * @return void
     */
    public function processSpecificationByAttribute(array &$element, CheckoutAttributeInterface $attribute): void;
}
