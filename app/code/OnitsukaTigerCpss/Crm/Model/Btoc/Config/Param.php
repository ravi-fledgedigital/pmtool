<?php
namespace OnitsukaTigerCpss\Crm\Model\Btoc\Config;

use Magento\Newsletter\Model\Subscriber;

class Param extends \Cpss\Crm\Model\Btoc\Config\Param
{
    const RULE_OPTIONAL = 'optional';
    const RULE_REQUIRED = 'required';
    const RULE_CONDITIONALLY_REQUIRED = 'conditionally_required';
    const RULE_ALPHA_NUMERIC_SPACE = 'alpha_numeric_space';
    const RULE_ALPHA_NUMERIC_SYMBOLS = 'alpha_numeric_symbols';
    const RULE_ALPHA_NUMERIC_SPACE_DASH = 'alpha_numeric_space_dash';
    const RULE_CLEAN = 'clean';
    const RULE_ALPHA_NUMERIC = 'alpha_numeric';
    // Max length(bytes)
    const PARAMS_LENGTH = [
        self::SITE_ID => 10,
        self::ACCESS_TOKEN => 256,
        self::MEMBER_ID => 10,
        self::EMAIL => 256,
        self::PASSWORD => 50,
        self::LASTNAME => 256,
        self::FIRSTNAME => 256,
        self::LASTNAME_KANA => 24,
        self::FIRSTNAME_KANA => 24,
        self::GENDER => 1,
        self::DOB => 8,
        self::COUNTRY_CODE => 2,
        self::POSTAL_CODE_1 => 5,
        self::PREFECTURE => 20,
        self::ADDRESS_1 => 150,
        self::ADDRESS_2 => 150,
        self::PHONE_1 => 8,
        self::NEWSLETTER => 1,
        self::OCCUPATION => 24
    ];
    // rest/V1/updateMemberInfo params
    const UPDATE_MEMBER_PARAMS = [
        parent::SITE_ID => parent::REQUIRED,
        parent::ACCESS_TOKEN => parent::REQUIRED,
        parent::MEMBER_ID => parent::REQUIRED,
        parent::EMAIL => parent::OPTIONAL,
        parent::PASSWORD => parent::OPTIONAL,
        parent::LASTNAME => parent::OPTIONAL,
        parent::FIRSTNAME => parent::OPTIONAL,
        parent::GENDER => parent::OPTIONAL,
        parent::DOB => parent::OPTIONAL,
        parent::COUNTRY_CODE => parent::OPTIONAL,
        parent::POSTAL_CODE_1 => parent::CONDITIONALLY_REQUIRED,
        parent::PREFECTURE => parent::OPTIONAL,
        parent::ADDRESS_1 => parent::OPTIONAL,
        parent::ADDRESS_2 => parent::OPTIONAL,
        parent::PHONE_1 => parent::CONDITIONALLY_REQUIRED,
        parent::NEWSLETTER => parent::OPTIONAL,
        parent::OCCUPATION => parent::OPTIONAL
    ];
    // rest/V1/updateMemberInfo params
    const REQUEST_UPDATE_MEMBER_PARAMS = [
        parent::SITE_ID => self::RULE_REQUIRED . '|alpha_numeric_symbols',
        parent::ACCESS_TOKEN => self::RULE_REQUIRED . '|alpha_numeric',
        parent::MEMBER_ID => self::RULE_REQUIRED . '|numeric',
        parent::EMAIL => self::RULE_OPTIONAL . '|email',
        parent::PASSWORD => self::RULE_OPTIONAL . '|password',
        parent::LASTNAME => self::RULE_OPTIONAL . '|clean|maxlength:255',
        parent::FIRSTNAME => self::RULE_OPTIONAL . '|clean|maxlength:255',
        parent::GENDER => self::RULE_OPTIONAL . '|numeric|maxlength:1|range:0,3',
        parent::DOB => self::RULE_OPTIONAL . '|numeric|dob|maxlength:8',
        parent::COUNTRY_CODE => self::RULE_OPTIONAL . '|alpha|maxlength:3',
        parent::POSTAL_CODE_1 => self::RULE_CONDITIONALLY_REQUIRED . '|postal_code|numeric',
        parent::PREFECTURE => self::RULE_OPTIONAL . '|maxlength:20',
        parent::ADDRESS_1 => self::RULE_OPTIONAL . '|special_characters|max_length_address|chaining_spaces|maxlength:255',
        parent::ADDRESS_2 => self::RULE_OPTIONAL . '|special_characters|max_length_address|chaining_spaces|maxlength:255',
        parent::PHONE_1 => self::RULE_CONDITIONALLY_REQUIRED . '|phone|numeric|min:8',
        parent::NEWSLETTER => self::RULE_OPTIONAL . '|range:' . Subscriber::STATUS_SUBSCRIBED . ',' . Subscriber::STATUS_UNCONFIRMED,
        parent::OCCUPATION => self::RULE_OPTIONAL . '|maxlength:24'
    ];
    const REQUEST_UPDATE_MEMBER_KR_PARAMS = [
        self::LASTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
        self::FIRSTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
    ];
    // rest/V1/registerMember params
    const  REQUEST_REGISTER_MEMBER_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|alpha_numeric_symbols',
        self::EMAIL => self::RULE_REQUIRED . '|email',
        self::PASSWORD => self::RULE_REQUIRED . '|password',
        self::LASTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
        self::FIRSTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
        self::GENDER => self::RULE_OPTIONAL . '|numeric|maxlength:1|range:0,3',
        self::DOB => self::RULE_REQUIRED . '|numeric|dob|maxlength:8',
        self::COUNTRY_CODE => self::RULE_REQUIRED . '|alpha|maxlength:3',
        self::POSTAL_CODE_1 => self::RULE_CONDITIONALLY_REQUIRED . '|postal_code|numeric',
        self::PREFECTURE => self::RULE_CONDITIONALLY_REQUIRED . '|maxlength:20',
        self::ADDRESS_1 => self::RULE_OPTIONAL . '|special_characters|max_length_address|chaining_spaces|maxlength:150',
        self::ADDRESS_2 => self::RULE_OPTIONAL . '|special_characters|max_length_address|chaining_spaces|maxlength:150',
        self::PHONE_1 => self::RULE_CONDITIONALLY_REQUIRED . '|phone|numeric|min:8',
        self::NEWSLETTER => self::RULE_OPTIONAL . '|range:' . Subscriber::STATUS_SUBSCRIBED . ',' . Subscriber::STATUS_UNCONFIRMED,
        self::OCCUPATION => self::RULE_OPTIONAL . '|maxlength:24'
    ];
    const REQUEST_REGISTER_MEMBER_KR_PARAMS = [
        self::LASTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
        self::FIRSTNAME => self::RULE_REQUIRED . '|clean|maxlength:255',
    ];
    // rest/V1/getMemberInfo params
    const REQUEST_GET_MEMBER_INFO_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|alpha_numeric_symbols',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|alpha_numeric',
        self::MEMBER_ID => self::RULE_REQUIRED . '|numeric',
    ];
    // rest/V1/getMemberOrderHistory/ params
    const REQUEST_MEMBER_ORDER_HISTORY_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|alpha_numeric_symbols',
        self::ACCESS_TOKEN => self::RULE_REQUIRED . '|alpha_numeric',
        self::MEMBER_ID => self::RULE_REQUIRED . '|numeric',
    ];
    // rest/V1/loginMember params
    const REQUEST_LOGIN_MEMBER_PARAMS = [
        self::SITE_ID => self::RULE_REQUIRED . '|alpha_numeric_symbols',
        self::EMAIL => self::RULE_REQUIRED . '|email',
        self::MEMBER_ID => self::RULE_CONDITIONALLY_REQUIRED . '|numeric',
        self::PASSWORD => self::RULE_REQUIRED
    ];
    const PARAMS_CUSTOM_RANGE = [
        self::NEWSLETTER => ['min' => Subscriber::STATUS_SUBSCRIBED, 'max' => Subscriber::STATUS_UNCONFIRMED],
    ];
    const CONDITIONALLY_AND_REQUIRED = [
        self::NEWSLETTER
    ];
}
