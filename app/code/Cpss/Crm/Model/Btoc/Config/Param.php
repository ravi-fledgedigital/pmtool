<?php

namespace Cpss\Crm\Model\Btoc\Config;

class Param
{
    // request parameter name
    const SITE_ID = 'siteId';
    const ACCESS_TOKEN = 'accessToken';
    const MEMBER_ID = 'memberId';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const LASTNAME = 'lastName';
    const FIRSTNAME = 'firstName';
    const LASTNAME_KANA = 'lastNameKana';
    const FIRSTNAME_KANA = 'firstNameKana';
    const GENDER = 'gender';
    const DOB = 'birthDay';
    const COUNTRY_CODE = 'countryCode';
    const POSTAL_CODE_1 = 'postalCode1';
    const POSTAL_CODE_2 = 'postalCode2';
    const PREFECTURE = 'prefecture';
    const ADDRESS_1 = 'address1';
    const ADDRESS_2 = 'address2';
    const PHONE_1 = 'phone1';
    const PHONE_2 = "phone2";
    const PHONE_3 = "phone3";
    const NEWSLETTER = "subscribeNewsLetter";
    const OCCUPATION = "occupation";


    // Is required?
    const REQUIRED = 1;
    const CONDITIONALLY_REQUIRED = 2;
    const OPTIONAL = 3;

    // Allowed characters
    const NUMERIC = 1; // Half width number only.
    const ALPHA_NUMERIC = 2; // Half width aplhabet and number.
    const ALPHA_NUMERIC_SYMBOLS = 3; // Half width aplhabet, number and symbols.
    const EMAIL_VALIDATION = 4; // Half width aplhabet and number and symbols as below.(.+_-@)
    const FULL_WIDTH_KANA_CHARACTERS = 5; // Full width kana characters.
    const ALPHA = 6; // Half width aplhabet only.
    const ALPHA_NUMERIC_SPACE = 7;
    const FULL_HALF_WIDTH_CHARACTERS = 8;
    const FULL_WIDTH_CHARACTERS = 9;

    const ALLOWED_CHAR_LABEL = [
        self::NUMERIC => 'Half width number only.',
        self::ALPHA_NUMERIC => 'Half width aplhabet and number.',
        self::ALPHA_NUMERIC_SYMBOLS => 'Half width aplhabet, number and symbols.',
        self::EMAIL_VALIDATION => 'Half width aplhabet and number and symbols as below.(.+_-@)',
        self::FULL_WIDTH_KANA_CHARACTERS => 'Full width kana characters.',
        self::ALPHA => 'Half width aplhabet only.',
        self::ALPHA_NUMERIC_SPACE => 'Half width aplhabet, number and space.',
        self::FULL_HALF_WIDTH_CHARACTERS => 'Half width aplhabet and number, and full width character.',
        self::FULL_WIDTH_CHARACTERS => 'Full width characters only.',
    ];

    //parameter that allowed prefix
    const INCLUDE_PREFIX = [
        self::PHONE_1 => '+',
    ];

    const PARAMS_ALLOWED_CHARACTERS = [
        self::SITE_ID => self::ALPHA_NUMERIC_SYMBOLS,
        self::ACCESS_TOKEN => self::ALPHA_NUMERIC,
        self::MEMBER_ID => self::NUMERIC,
        self::EMAIL => self::EMAIL_VALIDATION,
        self::PASSWORD => self::ALPHA_NUMERIC_SYMBOLS,
        self::LASTNAME => self::FULL_HALF_WIDTH_CHARACTERS,
        self::FIRSTNAME => self::FULL_HALF_WIDTH_CHARACTERS,
        self::LASTNAME_KANA => self::FULL_WIDTH_KANA_CHARACTERS,
        self::FIRSTNAME_KANA => self::FULL_WIDTH_KANA_CHARACTERS,
        self::GENDER => self::NUMERIC,
        self::DOB => self::NUMERIC,
        self::COUNTRY_CODE => self::ALPHA,
        self::POSTAL_CODE_1 => self::NUMERIC,
        self::POSTAL_CODE_2 => self::NUMERIC,
        self::PREFECTURE => self::FULL_WIDTH_CHARACTERS,
        self::ADDRESS_1 => self::FULL_HALF_WIDTH_CHARACTERS,
        self::ADDRESS_2 => self::FULL_HALF_WIDTH_CHARACTERS,
        self::PHONE_1 => self::NUMERIC,
        self::PHONE_2 => self::NUMERIC,
        self::PHONE_3 => self::NUMERIC,
        self::NEWSLETTER => self::NUMERIC,
        self::OCCUPATION => self::FULL_HALF_WIDTH_CHARACTERS
    ];

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
        self::POSTAL_CODE_1 => 3,
        self::POSTAL_CODE_2 => 4,
        self::PREFECTURE => 20,
        self::ADDRESS_1 => 150,
        self::ADDRESS_2 => 150,
        self::PHONE_1 => 6,
        self::PHONE_2 => 4,
        self::PHONE_3 => 4,
        self::NEWSLETTER => 1,
        self::OCCUPATION => 24
    ];

    const PARAMS_RANGE = [
        self::GENDER => ['min' => 0, 'max' => 3],
        self::NEWSLETTER => ['min' => 0, 'max' => 1],
        self::PHONE_1 => ['min' => 2, 'max' => 6]
    ];

    // Transaction type. Specified 1 or 2.
    // 1:Purchase 2:Return products/Cancel
    const TRANSACTION_TYPE_1 = 1;
    const TRANSACTION_TYPE_2 = 2;

    const TRANSACTION_TYPE_VALUES = [
        self::TRANSACTION_TYPE_1 => 'Purchase',
        self::TRANSACTION_TYPE_2 => 'Return Products/Cancel'
    ];

    // rest/V1/registerMember params
    const REGISTER_MEMBER_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::EMAIL => self::REQUIRED,
        self::PASSWORD => self::REQUIRED,
        self::LASTNAME => self::REQUIRED,
        self::FIRSTNAME => self::REQUIRED,
        self::LASTNAME_KANA => self::OPTIONAL,
        self::FIRSTNAME_KANA => self::OPTIONAL,
        self::GENDER => self::OPTIONAL,
        self::DOB => self::REQUIRED,
        self::COUNTRY_CODE => self::REQUIRED,
        self::POSTAL_CODE_1 => self::CONDITIONALLY_REQUIRED,
        self::POSTAL_CODE_2 => self::CONDITIONALLY_REQUIRED,
        self::PREFECTURE => self::CONDITIONALLY_REQUIRED,
        self::ADDRESS_1 => self::OPTIONAL,
        self::ADDRESS_2 => self::OPTIONAL,
        self::PHONE_1 => self::CONDITIONALLY_REQUIRED,
        self::PHONE_2 => self::CONDITIONALLY_REQUIRED,
        self::PHONE_3 => self::CONDITIONALLY_REQUIRED,
        self::NEWSLETTER => self::OPTIONAL,
        self::OCCUPATION => self::OPTIONAL
    ];

    // rest/V1/loginMember params
    const LOGIN_MEMBER_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::EMAIL => self::CONDITIONALLY_REQUIRED,
        self::MEMBER_ID => self::CONDITIONALLY_REQUIRED,
        self::PASSWORD => self::REQUIRED
    ];

    // rest/V1/getMemberInfo params
    const GET_MEMBER_INFO_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED,
    ];

    // rest/V1/checkToken params
    const CHECK_TOKEN = [
        self::SITE_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED,
    ];

    // rest/V1/deleteMember params
    const DELETE_MEMBER_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED,
    ];

    // rest/V1/updateMemberInfo params
    const UPDATE_MEMBER_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED,
        self::EMAIL => self::OPTIONAL,
        self::PASSWORD => self::OPTIONAL,
        self::LASTNAME => self::OPTIONAL,
        self::FIRSTNAME => self::OPTIONAL,
        self::LASTNAME_KANA => self::OPTIONAL,
        self::FIRSTNAME_KANA => self::OPTIONAL,
        self::GENDER => self::OPTIONAL,
        self::DOB => self::OPTIONAL,
        self::COUNTRY_CODE => self::OPTIONAL,
        self::POSTAL_CODE_1 => self::CONDITIONALLY_REQUIRED,
        self::POSTAL_CODE_2 => self::CONDITIONALLY_REQUIRED,
        self::PREFECTURE => self::OPTIONAL,
        self::ADDRESS_1 => self::OPTIONAL,
        self::ADDRESS_2 => self::OPTIONAL,
        self::PHONE_1 => self::CONDITIONALLY_REQUIRED,
        self::PHONE_2 => self::CONDITIONALLY_REQUIRED,
        self::PHONE_3 => self::CONDITIONALLY_REQUIRED,
        self::NEWSLETTER => self::OPTIONAL,
        self::OCCUPATION => self::OPTIONAL
    ];

    // rest/V1/getMemberOrderHistory/ params
    const MEMBER_ORDER_HISTORY_PARAMS = [
        self::SITE_ID => self::REQUIRED,
        self::ACCESS_TOKEN => self::REQUIRED,
        self::MEMBER_ID => self::REQUIRED
    ];
}
