<?php
namespace Cpss\Crm\Model\Btoc\Config;

class Result
{
    const SUCCESS = 0;
    const EMAIL_EXISTS = 1;
    const INVALID_PARAMS = 2;
    const INTERNAL_ERROR = 3;
    const AUTH_FAILED = 4;
    const ACCOUNT_LOCKED = 5;
    const ACCESS_DENIED = 6;
    const INVALID_PASSWORD_LENGTH = 7;

    public const RESULT_CODES = [
        self::SUCCESS => 'success',
        self::EMAIL_EXISTS => 'email exists',
        self::INVALID_PARAMS => 'invalid parameter',
        self::INTERNAL_ERROR => 'internal error',
        self::AUTH_FAILED => 'auth failed',
        self::ACCOUNT_LOCKED => 'account locked',
        self::ACCESS_DENIED => 'access denied',
        self::INVALID_PASSWORD_LENGTH => 'invalid password length - valid length from $1 to $2'
    ];
}
