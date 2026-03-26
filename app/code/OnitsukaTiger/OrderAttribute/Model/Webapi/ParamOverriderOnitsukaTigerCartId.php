<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Webapi;

/**
 * Replaces a "%onitsukatiger_cart_id%" value with the current authenticated customer's cart
 */
class ParamOverriderOnitsukaTigerCartId  extends \Magento\Quote\Model\Webapi\ParamOverriderCartId
{

}
