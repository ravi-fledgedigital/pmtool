<?php
namespace Cpss\Crm\Api\Btoc;

interface LoginInterface {

    const PARAM_LENGTH = [
        'siteId' => 10,
        'email' => 256,
        'memberId' => 10,
        'password' => 64
    ];
    
    /**
     * loginMember
     *
     * @return mixed
     */
    public function loginMember();
}