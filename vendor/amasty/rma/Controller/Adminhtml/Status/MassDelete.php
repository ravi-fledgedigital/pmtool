<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Status;

use Amasty\Rma\Controller\Adminhtml\AbstractStatus;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractStatus
{
    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        StatusRepositoryInterface $repository,
        CollectionFactory $collectionFactory,
        Filter $filter,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->logger = $logger;
    }

    /**
     * Mass action execution
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();

        /** @var \Amasty\Rma\Model\Status\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedStatuses = 0;
        $failedStatuses = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $status) {
                try {
                    $this->repository->delete($status);
                    $deletedStatuses++;
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(
                        __($e->getMessage())
                    );
                    $failedStatuses++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting status with ID %1. Error: %2'),
                        [$status->getStatusId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedStatuses !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 status(es) has been successfully deleted', $deletedStatuses)
            );
        }

        if ($failedStatuses !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 status(es) has been failed to delete', $failedStatuses)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
