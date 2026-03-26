<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Gthk\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Gthk\Api\Data\GthkInterface;
use OnitsukaTiger\Gthk\Api\Data\GthkInterfaceFactory;
use OnitsukaTiger\Gthk\Api\Data\GthkSearchResultsInterfaceFactory;
use OnitsukaTiger\Gthk\Api\GthkRepositoryInterface;
use OnitsukaTiger\Gthk\Model\ResourceModel\Gthk as ResourceGthk;
use OnitsukaTiger\Gthk\Model\ResourceModel\Gthk\CollectionFactory as GthkCollectionFactory;

class GthkRepository implements GthkRepositoryInterface
{

    /**
     * @var ResourceGthk
     */
    protected $resource;

    /**
     * @var GthkCollectionFactory
     */
    protected $gthkCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var Gthk
     */
    protected $searchResultsFactory;

    /**
     * @var GthkInterfaceFactory
     */
    protected $gthkFactory;


    /**
     * @param ResourceGthk $resource
     * @param GthkInterfaceFactory $gthkFactory
     * @param GthkCollectionFactory $gthkCollectionFactory
     * @param GthkSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceGthk $resource,
        GthkInterfaceFactory $gthkFactory,
        GthkCollectionFactory $gthkCollectionFactory,
        GthkSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->gthkFactory = $gthkFactory;
        $this->gthkCollectionFactory = $gthkCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(GthkInterface $gthk)
    {
        try {
            $this->resource->save($gthk);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the GHTK: %1',
                $exception->getMessage()
            ));
        }
        return $gthk;
    }

    /**
     * @inheritDoc
     */
    public function get($gthkId)
    {
        $gthk = $this->gthkFactory->create();
        $this->resource->load($gthk, $gthkId);
        if (!$gthk->getId()) {
            throw new NoSuchEntityException(__('Gthk with id "%1" does not exist.', $gthkId));
        }
        return $gthk;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->gthkCollectionFactory->create();

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
    public function delete(GthkInterface $gthk)
    {
        try {
            $gthkModel = $this->gthkFactory->create();
            $this->resource->load($gthkModel, $gthk->getGthkId());
            $this->resource->delete($gthkModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the GHTK: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($gthkId)
    {
        return $this->delete($this->get($gthkId));
    }
}

