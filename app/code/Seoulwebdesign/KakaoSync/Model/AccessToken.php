<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Model;

use Magento\Framework\Model\AbstractModel;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;

class AccessToken extends AbstractModel implements AccessTokenInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken::class);
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenId()
    {
        return $this->getData(self::ACCESS_TOKEN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAccessTokenId($accessTokenId)
    {
        return $this->setData(self::ACCESS_TOKEN_ID, $accessTokenId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getKakaoCustomerId()
    {
        return $this->getData(self::KAKAO_CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setKakaoCustomerId($kakao_customer_id)
    {
        return $this->setData(self::KAKAO_CUSTOMER_ID, $kakao_customer_id);
    }

    /**
     * @inheritDoc
     */
    public function getTokenType()
    {
        return $this->getData(self::TOKEN_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setTokenType($tokenType)
    {
        return $this->setData(self::TOKEN_TYPE, $tokenType);
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken()
    {
        return $this->getData(self::ACCESS_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setAccessToken($accessToken)
    {
        return $this->setData(self::ACCESS_TOKEN, $accessToken);
    }

    /**
     * @inheritDoc
     */
    public function getExpiresIn()
    {
        return $this->getData(self::EXPIRES_IN);
    }

    /**
     * @inheritDoc
     */
    public function setExpiresIn($expiresIn)
    {
        return $this->setData(self::EXPIRES_IN, $expiresIn);
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken()
    {
        return $this->getData(self::REFRESH_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setRefreshToken($refreshToken)
    {
        return $this->setData(self::REFRESH_TOKEN, $refreshToken);
    }

    /**
     * @inheritDoc
     */
    public function getRefreshTokenExpiresIn()
    {
        return $this->getData(self::REFRESH_TOKEN_EXPIRES_IN);
    }

    /**
     * @inheritDoc
     */
    public function setRefreshTokenExpiresIn($refreshTokenExpiresIn)
    {
        return $this->setData(self::REFRESH_TOKEN_EXPIRES_IN, $refreshTokenExpiresIn);
    }

    /**
     * @inheritDoc
     */
    public function getScope()
    {
        return $this->getData(self::SCOPE);
    }

    /**
     * @inheritDoc
     */
    public function setScope($scope)
    {
        return $this->setData(self::SCOPE, $scope);
    }

    /**
     * @inheritDoc
     */
    public function getIdToken()
    {
        return $this->getData(self::ID_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setIdToken($idToken)
    {
        return $this->setData(self::ID_TOKEN, $idToken);
    }
}
