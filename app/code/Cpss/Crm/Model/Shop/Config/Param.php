<?php
//phpcs:ignoreFile
namespace Cpss\Crm\Model\Shop\Config;

class Param
{
    // request parameter name
    const SITE_ID = 'siteId';
    const SHOP_ID = 'shopId';
    const SHOP_PASS = 'password';
    const ACCESS_TOKEN = 'accessToken';
    const MEMBER_ID = 'memberId';
    const COUNTRY_CODE = 'countryCode';
    const PURCHASE_ID = 'purchaseId';
    const USED_POINT = 'usedPoint';
    const POINT_HISTORY_ID = 'pointHistoryId';
    const TRANSACTION_TYPE = 'transactionType';
    const RETURN_POINT = 'returnPoint';

    const START_DATE = "startDate";
    const END_DATE = "endDate";
    const TERMINAL_NO = "terminalNo";


    // Is required?
    const REQUIRED = 1;
    const CONDITIONALLY_REQUIRED = 2;
    const OPTIONAL = 3;

    const PARAMS_REQUIREMENT = [
        self::SITE_ID => self::REQUIRED,
        self::SHOP_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED,
        self::PURCHASE_ID => self::REQUIRED,
        self::USED_POINT => self::CONDITIONALLY_REQUIRED,
        self::POINT_HISTORY_ID => self::CONDITIONALLY_REQUIRED,
        self::TRANSACTION_TYPE => self::REQUIRED
    ];

    // Allowed characters
    const NUMERIC = 1; // Half width number only.
    const ALPHA_NUMERIC = 2; // Half width aplhabet and number.
    const ALPHA_NUMERIC_SYMBOLS = 3; // Half width aplhabet, number and symbols.

    const ALLOWED_CHAR_LABEL = [
        self::NUMERIC => 'Half width number only.',
        self::ALPHA_NUMERIC => 'Half width aplhabet and number.',
        self::ALPHA_NUMERIC_SYMBOLS => 'Half width aplhabet, number and symbols.'
    ];

    const PARAMS_ALLOWED_CHARACTERS = [
        self::SITE_ID => self::ALPHA_NUMERIC_SYMBOLS,
        self::SHOP_ID => self::ALPHA_NUMERIC_SYMBOLS,
        self::ACCESS_TOKEN => self::ALPHA_NUMERIC_SYMBOLS,
        self::MEMBER_ID => self::NUMERIC, //self::ALPHA_NUMERIC_SYMBOLS,
        self::COUNTRY_CODE => self::NUMERIC,
        self::PURCHASE_ID => self::ALPHA_NUMERIC_SYMBOLS,
        self::USED_POINT => self::NUMERIC,
        self::POINT_HISTORY_ID => self::ALPHA_NUMERIC_SYMBOLS,
        self::TRANSACTION_TYPE => self::NUMERIC,
        self::ACCESS_TOKEN => self::ALPHA_NUMERIC_SYMBOLS,
        self::TERMINAL_NO => self::NUMERIC,
        self::START_DATE => self::NUMERIC,
        self::END_DATE => self::NUMERIC,
        self::SHOP_PASS => self::ALPHA_NUMERIC_SYMBOLS,
    ];

    // Max length(bytes)
    const PARAMS_LENGTH = [
        self::SITE_ID => 10,
        self::SHOP_ID => 5,
        self::ACCESS_TOKEN => 64,
        self::MEMBER_ID => 64,
        self::COUNTRY_CODE => 3,
        self::PURCHASE_ID => 28, // 24 original
        self::USED_POINT => 10,
        self::POINT_HISTORY_ID => 64,
        self::TRANSACTION_TYPE => 1,
        self::TERMINAL_NO => 5,
        self::START_DATE => 8,
        self::END_DATE => 8,
        self::SHOP_PASS => 64,
    ];

    // Transaction type. Specified 1 or 2.
    // 1:Purchase 2:Return products/Cancel
    const TRANSACTION_TYPE_1 = 1;
    const TRANSACTION_TYPE_2 = 2;

    const TRANSACTION_TYPE_3 = 3;

    const TRANSACTION_TYPE_VALUES = [
        self::TRANSACTION_TYPE_1 => 'Purchase',
        self::TRANSACTION_TYPE_2 => 'Return Products/Cancel',
        self::TRANSACTION_TYPE_3 => 'Exchange'
    ];

    // rest/V1/registerShopReceipt params
    const REGISTER_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::SHOP_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::CONDITIONALLY_REQUIRED,
        self::COUNTRY_CODE => self::CONDITIONALLY_REQUIRED,
        self::PURCHASE_ID => self::REQUIRED,
        self::USED_POINT => self::CONDITIONALLY_REQUIRED,
        self::POINT_HISTORY_ID => self::CONDITIONALLY_REQUIRED,
        self::TRANSACTION_TYPE => self::REQUIRED
    ];

    // rest/V1/DeleteReceipt params
    const DELETE_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::SHOP_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::PURCHASE_ID => self::REQUIRED
    ];

    // rest/V1/loginShop
    const SHOP_LOGIN_SHOP_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::SHOP_ID => self::REQUIRED,
        self::SHOP_PASS => self::REQUIRED,
    ];

    // rest/V1/getShopOrderHistory
    const SHOP_ORDER_HISTORY_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::SHOP_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::START_DATE  => self::REQUIRED,
        self::END_DATE  => self::REQUIRED,
        self::TERMINAL_NO => self::OPTIONAL,
        self::TRANSACTION_TYPE => self::OPTIONAL,
        self::MEMBER_ID => self::OPTIONAL,
        self::PURCHASE_ID => self::OPTIONAL
    ];
}
