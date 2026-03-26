<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api\Data;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutEntityInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;

interface EntityDataInterface extends CheckoutEntityInterface, CustomAttributesDataInterface
{

}
