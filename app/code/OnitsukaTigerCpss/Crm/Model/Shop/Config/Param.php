<?php

namespace OnitsukaTigerCpss\Crm\Model\Shop\Config;

use Cpss\Crm\Model\Shop\Config\Param as CrmParam;

class Param extends CrmParam
{
    const RULE_OPTIONAL = 'optional';
    const RULE_REQUIRED = 'required';
    const RULE_CONDITIONALLY_REQUIRED = 'conditionally_required';
    const RULE_ALPHA_NUMERIC_SPACE = 'alpha_numeric_space';
    const RULE_ALPHA_NUMERIC_SYMBOLS = 'alpha_numeric_symbols';
    const RULE_ALPHA_NUMERIC_SPACE_DASH = 'alpha_numeric_space_dash';
    const RULE_CLEAN = 'clean';
    const RULE_DEPEND_ON = 'depend_on';
    const RULE_DEPEND_ON_MIN = 'depend_on_min';
    // only for memberId | transactionType
    const RULE_DEPEND_WITH = 'depend_with';
    const RULE_ALPHA_NUMERIC = 'alpha_numeric';
    const RULE_NUMBER_UNDERSCORE = 'number_underscore';
    const RULE_PURCHASE_ID = 'purchase_id';

    // rest/V1/loginShop
    const REQUEST_SHOP_LOGIN_SHOP_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|maxlength:6',
        self::SHOP_PASS => self::RULE_REQUIRED . '|maxlength:64',
    ];
    // rest/V1/loginShop
    const KR_REQUEST_SHOP_LOGIN_SHOP_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|maxlength:8',
        self::SHOP_PASS => self::RULE_REQUIRED . '|maxlength:64',
    ];
    // rest/V1/registerShopReceipt params
    const REQUEST_REGISTER_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:6',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        self::MEMBER_ID => self::RULE_CONDITIONALLY_REQUIRED . '|numeric' . '|maxlength:64',
        self::COUNTRY_CODE => self::RULE_CONDITIONALLY_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:3',
        //self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE,
        self::USED_POINT => self::RULE_CONDITIONALLY_REQUIRED . '|numeric|maxlength:10|depend_on_min:' . self::POINT_HISTORY_ID,
        self::POINT_HISTORY_ID => self::RULE_CONDITIONALLY_REQUIRED . '|alpha_numeric|maxlength:64|depend_on_min:' . self::USED_POINT,
        self::TRANSACTION_TYPE => self::RULE_REQUIRED . '|numeric|maxlength:1|depend_with:' . self::MEMBER_ID . '|depend_with:' . self::COUNTRY_CODE . '|range:1,2',
    ];
    // rest/V1/registerShopReceipt params
    const KR_REQUEST_REGISTER_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:8',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        self::MEMBER_ID => self::RULE_CONDITIONALLY_REQUIRED . '|numeric' . '|maxlength:64',
        self::COUNTRY_CODE => self::RULE_CONDITIONALLY_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:3',
        //self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:34|' . self::RULE_NUMBER_UNDERSCORE,
        self::USED_POINT => self::RULE_CONDITIONALLY_REQUIRED . '|numeric|maxlength:10|depend_on_min:' . self::POINT_HISTORY_ID,
        self::POINT_HISTORY_ID => self::RULE_CONDITIONALLY_REQUIRED . '|alpha_numeric|maxlength:64|depend_on_min:' . self::USED_POINT,
        self::TRANSACTION_TYPE => self::RULE_REQUIRED . '|numeric|maxlength:1|depend_with:' . self::MEMBER_ID . '|depend_with:' . self::COUNTRY_CODE . '|range:1,2',
    ];
    // rest/V1/deleteShopReceipt
    const REQUEST_DELETE_PARAMS = [
        self::SITE_ID =>  self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:6',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        //self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE,
    ];
    // rest/V1/deleteShopReceipt
    const KR_REQUEST_DELETE_PARAMS = [
        self::SITE_ID =>  self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:8',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        //self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:34|' . self::RULE_NUMBER_UNDERSCORE,
    ];
    // rest/V1/getShopOrderHistory
    const REQUEST_SHOP_ORDER_HISTORY_PARAMS = [
        self::SITE_ID =>  self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:6',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        //self::PURCHASE_ID => self::RULE_OPTIONAL . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_OPTIONAL . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE,
        self::START_DATE  => self::RULE_REQUIRED . '|numeric|maxlength:8',
        self::END_DATE  => self::RULE_REQUIRED . '|numeric|maxlength:8',
        self::TERMINAL_NO => self::RULE_OPTIONAL . '|numeric|maxlength:5',
        self::TRANSACTION_TYPE => self::RULE_OPTIONAL . '|numeric|maxlength:1|range:1,2',
        self::MEMBER_ID => self::RULE_OPTIONAL . '|numeric' . '|maxlength:64',
    ];
    // rest/V1/getShopOrderHistory
    const KR_REQUEST_SHOP_ORDER_HISTORY_PARAMS = [
        self::SITE_ID =>  self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|numeric|maxlength:10',
        self::SHOP_ID => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:8',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|alpha_numeric|maxlength:64',
        //self::PURCHASE_ID => self::RULE_OPTIONAL . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:32|' . self::RULE_NUMBER_UNDERSCORE . '|' . self::RULE_PURCHASE_ID,
        self::PURCHASE_ID => self::RULE_OPTIONAL . '|' . self::RULE_ALPHA_NUMERIC_SYMBOLS . '|maxlength:34|' . self::RULE_NUMBER_UNDERSCORE,
        self::START_DATE  => self::RULE_REQUIRED . '|numeric|maxlength:8',
        self::END_DATE  => self::RULE_REQUIRED . '|numeric|maxlength:8',
        self::TERMINAL_NO => self::RULE_OPTIONAL . '|numeric|maxlength:5',
        self::TRANSACTION_TYPE => self::RULE_OPTIONAL . '|numeric|maxlength:1|range:1,2',
        self::MEMBER_ID => self::RULE_OPTIONAL . '|numeric' . '|maxlength:64',
    ];
}
