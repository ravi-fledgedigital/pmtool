<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AccessTokenRepositoryInterface
{

    /**
     * Save access_token
     *
     * @param \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface $accessToken
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface $accessToken
    );

    /**
     * Create an empty object
     *
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     */
    public function createEmpty();

    /**
     * Retrieve access_token
     *
     * @param string $accessTokenId
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($accessTokenId);

    /**
     * Retrieve access_token
     *
     * @param string $customerId
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByCustomerId($customerId);

    /**
     * Get by kakao customer Id
     *
     * @param int $kakaoCustomerId
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByKakaoCustomerId($kakaoCustomerId);

    /**
     * Get list
     *
     * Retrieve access_token matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Get all token items
     *
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllToken();

    /**
     * Delete access_token
     *
     * @param \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface $accessToken
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface $accessToken
    );

    /**
     * Delete access_token by ID
     *
     * @param string $accessTokenId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($accessTokenId);
}
