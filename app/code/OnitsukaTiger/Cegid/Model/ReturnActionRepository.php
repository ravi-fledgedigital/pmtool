<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Cegid\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface;
use OnitsukaTiger\Cegid\Api\Data\ReturnActionInterfaceFactory;
use OnitsukaTiger\Cegid\Api\Data\ReturnActionSearchResultsInterfaceFactory;
use OnitsukaTiger\Cegid\Api\ReturnActionRepositoryInterface;
use OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction as ResourceReturnAction;
use OnitsukaTiger\Cegid\Model\ResourceModel\ReturnAction\CollectionFactory as ReturnActionCollectionFactory;

class ReturnActionRepository implements ReturnActionRepositoryInterface
{

    /**
     * @var ReturnAction
     */
    protected $searchResultsFactory;

    /**
     * @var ReturnActionCollectionFactory
     */
    protected $returnActionCollectionFactory;

    /**
     * @var ResourceReturnAction
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ReturnActionInterfaceFactory
     */
    protected $returnActionFactory;


    /**
     * @param ResourceReturnAction $resource
     * @param ReturnActionInterfaceFactory $returnActionFactory
     * @param ReturnActionCollectionFactory $returnActionCollectionFactory
     * @param ReturnActionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceReturnAction $resource,
        ReturnActionInterfaceFactory $returnActionFactory,
        ReturnActionCollectionFactory $returnActionCollectionFactory,
        ReturnActionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->returnActionFactory = $returnActionFactory;
        $this->returnActionCollectionFactory = $returnActionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }


    /**
     * @param ReturnActionInterface $returnAction
     * @return ReturnActionInterface
     * @throws CouldNotSaveException
     */
    public function save(ReturnActionInterface $returnAction): \OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface
    {
        try {
            $this->resource->save($returnAction);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the returnAction: %1',
                $exception->getMessage()
            ));
        }
        return $returnAction;
    }


    /**
     * @param $returnActionId
     * @return ReturnActionInterface
     * @throws NoSuchEntityException
     */
    public function get($returnActionId):\OnitsukaTiger\Cegid\Api\Data\ReturnActionInterface
    {
        $returnAction = $this->returnActionFactory->create();
        $this->resource->load($returnAction, $returnActionId);
        if (!$returnAction->getId()) {
            throw new NoSuchEntityException(__('ReturnAction with id "%1" does not exist.', $returnActionId));
        }
        return $returnAction;
    }


    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \OnitsukaTiger\Cegid\Api\Data\ReturnActionSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ):\OnitsukaTiger\Cegid\Api\Data\ReturnActionSearchResultsInterface {
        $collection = $this->returnActionCollectionFactory->create();

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
     * @param ReturnActionInterface $returnAction
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ReturnActionInterface $returnAction): bool
    {
        try {
            $returnActionModel = $this->returnActionFactory->create();
            $this->resource->load($returnActionModel, $returnAction->getReturnactionId());
            $this->resource->delete($returnActionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ReturnAction: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }


    /**
     * @param $returnActionId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($returnActionId): bool
    {
        return $this->delete($this->get($returnActionId));
    }
}
