<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api;

/**
 * @api
 */
interface CheckoutDataRepositoryInterface
{
    /**
     * Save Data from Frontend Checkout
     *
     * @param int $onitsukatigerCartId
     * @param string $checkoutFormCode
     * @param string $shippingMethodCode
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData
     * @throws \Magento\Framework\Exception\InputException
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface
     */
    public function save(
        $onitsukatigerCartId,
        $checkoutFormCode,
        $shippingMethodCode,
        \OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface $entityData
    );
}
