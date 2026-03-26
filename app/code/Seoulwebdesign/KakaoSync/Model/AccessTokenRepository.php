<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Seoulwebdesign\KakaoSync\Api\AccessTokenRepositoryInterface;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterfaceFactory;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenSearchResultsInterface;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenSearchResultsInterfaceFactory;
use Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken as ResourceAccessToken;
use Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken\CollectionFactory as AccessTokenCollectionFactory;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{

    /**
     * @var AccessTokenCollectionFactory
     */
    protected $accessTokenCollectionFactory;

    /**
     * @var ResourceAccessToken
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var AccessToken
     */
    protected $searchResultsFactory;

    /**
     * @var AccessTokenInterfaceFactory
     */
    protected $accessTokenFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param ResourceAccessToken $resource
     * @param AccessTokenInterfaceFactory $accessTokenFactory
     * @param AccessTokenCollectionFactory $accessTokenCollectionFactory
     * @param AccessTokenSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceAccessToken $resource,
        AccessTokenInterfaceFactory $accessTokenFactory,
        AccessTokenCollectionFactory $accessTokenCollectionFactory,
        AccessTokenSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->accessTokenFactory = $accessTokenFactory;
        $this->accessTokenCollectionFactory = $accessTokenCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function save(AccessTokenInterface $accessToken)
    {
        try {
            $this->resource->save($accessToken);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the accessToken: %1',
                $exception->getMessage()
            ));
        }
        return $accessToken;
    }

    /**
     * @inheritDoc
     */
    public function createEmpty()
    {
        return $this->accessTokenFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function get($accessTokenId)
    {
        $accessToken = $this->accessTokenFactory->create();
        $this->resource->load($accessToken, $accessTokenId);
        if (!$accessToken->getId()) {
            throw new NoSuchEntityException(__('access_token with id "%1" does not exist.', $accessTokenId));
        }
        return $accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId($customerId)
    {
        $accessToken = $this->accessTokenFactory->create();
        $this->resource->load($accessToken, $customerId, AccessTokenInterface::CUSTOMER_ID);
        if (!$accessToken->getId()) {
            throw new NoSuchEntityException(__('Access_token with customer id "%1" does not exist.', $customerId));
        }
        return $accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getByKakaoCustomerId($kakaoCustomerId)
    {
        $accessToken = $this->accessTokenFactory->create();
        $this->resource->load($accessToken, $kakaoCustomerId, AccessTokenInterface::KAKAO_CUSTOMER_ID);
        if (!$accessToken->getId()) {
            throw new NoSuchEntityException(
                __(
                    'Access_token with kakao customer id "%1" does not exist.',
                    $kakaoCustomerId
                )
            );
        }
        return $accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->accessTokenCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getAllToken()
    {
        return $this->getList($this->searchCriteriaBuilder->create())->getItems();
    }

    /**
     * @inheritDoc
     */
    public function delete(AccessTokenInterface $accessToken)
    {
        try {
            $accessTokenModel = $this->accessTokenFactory->create();
            $this->resource->load($accessTokenModel, $accessToken->getAccessTokenId());
            $this->resource->delete($accessTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the access_token: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($accessTokenId)
    {
        return $this->delete($this->get($accessTokenId));
    }
}
