<?php
namespace Cpss\Crm\Model\Shop\Config;

class Result
{
    const SUCCESS = 0;
    const AUTH_FAILED = 1;
    const INVALID_PARAMS = 2;
    const INVALID_SPECIFIED_PERIOD = 3;
    const INTERNAL_ERROR = 4;
    const RECEIPT_NOT_REGISTERED = 5;

    public const RESULT_CODES = [
        self::SUCCESS => 'success',
        self::AUTH_FAILED => 'auth failed',
        self::INVALID_PARAMS => 'invalid parameter',
        self::INVALID_SPECIFIED_PERIOD => 'Invalid specified period',
        self::INTERNAL_ERROR => 'internal error',
        self::RECEIPT_NOT_REGISTERED => 'receipt not registered'
    ];

    // public const RESULT_MESSAGE = [
    //     self::SUCCESS => 'success',
    //     self::AUTH_FAILED => 'auth failed',
    //     self::INVALID_PARAMS => 'invalid parameter',
    //     self::INVALID_SPECIFIED_PERIOD => 'Invalid specified period',
    //     self::INTERNAL_ERROR => 'internal error',
    //     self::RECEIPT_NOT_REGISTERED => 'receipt not registered'
    // ]
}