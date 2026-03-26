<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request;

use Amasty\Rma\Model\Request\Repository;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

abstract class AbstractMassDelete extends Action
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $requestCollectionFactory;

    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $requestCollectionFactory,
        Repository $repository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->filter = $filter;
        $this->requestCollectionFactory = $requestCollectionFactory;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();
        /** @var \Amasty\Rma\Model\Request\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->requestCollectionFactory->create());
        $deleted = 0;
        $failed = 0;

        foreach ($collection->getItems() as $request) {
            try {
                $this->repository->delete($request);
                $deleted++;
            } catch (CouldNotDeleteException $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
                $failed++;
            } catch (\Exception $e) {
                $this->logger->error(
                    __('Error occurred while deleting Request with ID %1. Error: %2'),
                    [$request->getId(), $e->getMessage()]
                );
            }
        }

        if ($deleted !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 request(s) has been successfully deleted', $deleted)
            );
        }

        if ($failed !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 request(s) has been failed to delete', $failed)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
