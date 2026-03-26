<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Api\Data;

interface AccessTokenInterface
{

    public const ACCESS_TOKEN_ID = 'access_token_id';
    public const CUSTOMER_ID = 'customer_id';
    public const KAKAO_CUSTOMER_ID = 'kakao_customer_id';
    public const REFRESH_TOKEN = 'refresh_token';
    public const TOKEN_TYPE = 'token_type';
    public const EXPIRES_IN = 'expires_in';
    public const ID_TOKEN = 'id_token';
    public const ACCESS_TOKEN = 'access_token';
    public const REFRESH_TOKEN_EXPIRES_IN = 'refresh_token_expires_in';
    public const SCOPE = 'scope';

    /**
     * Get access_token_id
     *
     * @return string|null
     */
    public function getAccessTokenId();

    /**
     * Set access_token_id
     *
     * @param string $accessTokenId
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setAccessTokenId($accessTokenId);

    /**
     * Get customer_id
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param int $customer_id
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setCustomerId($customer_id);

    /**
     * Get kakao_customer_id
     *
     * @return int|null
     */
    public function getKakaoCustomerId();

    /**
     * Set kakao_ustomer_id
     *
     * @param int $kakao_customer_id
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setKakaoCustomerId($kakao_customer_id);

    /**
     * Get token_type
     *
     * @return string|null
     */
    public function getTokenType();

    /**
     * Set token_type
     *
     * @param string $tokenType
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setTokenType($tokenType);

    /**
     * Get access_token
     *
     * @return string|null
     */
    public function getAccessToken();

    /**
     * Set access_token
     *
     * @param string $accessToken
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setAccessToken($accessToken);

    /**
     * Get expires_in
     *
     * @return string|null
     */
    public function getExpiresIn();

    /**
     * Set expires_in
     *
     * @param string $expiresIn
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setExpiresIn($expiresIn);

    /**
     * Get refresh_token
     *
     * @return string|null
     */
    public function getRefreshToken();

    /**
     * Set refresh_token
     *
     * @param string $refreshToken
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setRefreshToken($refreshToken);

    /**
     * Get refresh_token_expires_in
     *
     * @return string|null
     */
    public function getRefreshTokenExpiresIn();

    /**
     * Set refresh_token_expires_in
     *
     * @param string $refreshTokenExpiresIn
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setRefreshTokenExpiresIn($refreshTokenExpiresIn);

    /**
     * Get scope
     *
     * @return string|null
     */
    public function getScope();

    /**
     * Set scope
     *
     * @param string $scope
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setScope($scope);

    /**
     * Get id_token
     *
     * @return string|null
     */
    public function getIdToken();

    /**
     * Set id_token
     *
     * @param string $idToken
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function setIdToken($idToken);
}
