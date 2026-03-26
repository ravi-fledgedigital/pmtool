<?php
namespace Cpss\Crm\Api\Shop;

/**
 * LoginInterface Interface
 * @api
 */
interface LoginInterface
{    
    const SITE_ID = 'siteId';
    const SHOP_ID = 'shopId';
    const PASSWORD = 'password';

    const SITE_ID_LENGTH = 10;
    const SHOP_ID_ID_LENGTH = 10;
    const PASSWORD_LENGTH = 64;
    
    /**
     * loginShop
     *
     * @return mixed
     */
    public function loginShop();
}