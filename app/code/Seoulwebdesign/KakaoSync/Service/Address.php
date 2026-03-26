<?php

namespace Seoulwebdesign\KakaoSync\Service;

class Address
{
    /**
     * @var Kakao
     */
    protected $kakao;
    /**
     * @var string
     */
    private $token;

    /**
     * @param Kakao $kakao
     */
    public function __construct(
        Kakao $kakao
    ) {
        $this->kakao = $kakao;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get token
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Pull the address
     *
     * @return array|mixed
     */
    public function pullAddress()
    {
        $response  = $this->kakao->getShippingAddress($this->getToken());
        if (isset($response['shipping_addresses'])) {
            return $response['shipping_addresses'];
        }
        return [];
    }
}
