<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel\Collection;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
     */
    private ReasonRepositoryInterface $repository;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Action\Context $context
     * @param ReasonRepositoryInterface $repository
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param LoggerInterface $logger
     */
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

        /** @var Collection $collection */
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
