<?php
namespace Cpss\Crm\Helper;

class ApiError
{
    const RES_CODE = 'ResCode';
    const RES_INFO = 'ResInfo';

    const RES_CODES = [
        0 => "success",
        1 => "email exists",
        2 => "invalid parameter",
        3 => "internal error",
        4 => "auth failed",
        5 => "account locked",
        6 => "access denined"
    ];

    const RES_INFOS = [
        0 => "成功",
        1 => "すでにメールアドレスが使われている。",
        2 => "パラメータに誤りがある。",
        3 => "サーバ側でエラーが発生した。",
        4 => "認証に失敗した。",
        5 => "アカウントロックされている。",
        6 => "APIの受付が拒否された。"
    ];
}