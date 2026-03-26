<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Reason;

use Amasty\Rma\Controller\Adminhtml\AbstractReason;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Model\Reason\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
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
        ReasonRepositoryInterface $repository,
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

        /** @var \Amasty\Rma\Model\Reason\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedReasons = 0;
        $failedReasons = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $reason) {
                try {
                    $this->repository->delete($reason);
                    $deletedReasons++;
                } catch (LocalizedException $e) {
                    $failedReasons++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting reason with ID %1. Error: %2'),
                        [$reason->getReasonId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedReasons !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 reason(s) has been successfully deleted', $deletedReasons)
            );
        }

        if ($failedReasons !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 reason(s) has been failed to delete', $failedReasons)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
