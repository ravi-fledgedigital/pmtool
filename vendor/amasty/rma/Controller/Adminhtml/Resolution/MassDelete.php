<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Resolution;

use Amasty\Rma\Controller\Adminhtml\AbstractResolution;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractResolution
{
    /**
     * @var ResolutionRepositoryInterface
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
        ResolutionRepositoryInterface $repository,
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

        /** @var \Amasty\Rma\Model\Resolution\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedResolutions = 0;
        $failedResolutions = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $resolution) {
                try {
                    $this->repository->delete($resolution);
                    $deletedResolutions++;
                } catch (LocalizedException $e) {
                    $failedResolutions++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting resolution with ID %1. Error: %2'),
                        [$resolution->getResolutionId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedResolutions !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 resolution(s) has been successfully deleted', $deletedResolutions)
            );
        }

        if ($failedResolutions !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 resolution(s) has been failed to delete', $failedResolutions)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
