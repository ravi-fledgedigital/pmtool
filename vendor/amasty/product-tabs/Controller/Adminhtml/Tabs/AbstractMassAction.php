<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Tabs;

use Amasty\CustomTabs\Controller\Adminhtml\Tabs;
use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractMassAction
 * @package Amasty\CustomTabs\Controller\Adminhtml\Tabs
 */
abstract class AbstractMassAction extends Tabs
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\Repository
     */
    protected $repository;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\TabsFactory
     */
    protected $modelFactory;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        LoggerInterface $logger,
        \Amasty\CustomTabs\Model\Tabs\Repository $repository,
        \Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory $collectionFactory,
        \Amasty\CustomTabs\Model\Tabs\TabsFactory $modelFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->collectionFactory = $collectionFactory;
        $this->modelFactory = $modelFactory;
    }

    /**
     * Execute action for group
     *
     * @param TabsInterface $tab
     */
    abstract protected function itemAction(TabsInterface $tab);

    /**
     * Mass action execution
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider(); // compatibility with Mass Actions on Magento 2.1.0
        /** @var \Amasty\CustomTabs\Model\Tabs\ResourceModel\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addDefaultStore();
        $collection = $this->filter->getCollection($collection);

        $collectionSize = $collection->getSize();
        if ($collectionSize) {
            try {
                $updatedCount = 0;
                foreach ($collection->getItems() as $model) {
                    try {
                        $this->itemAction($model);
                        $updatedCount++;
                    } catch (LocalizedException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }
                }

                $this->messageManager->addSuccessMessage($this->getSuccessMessage($updatedCount));
            } catch (CouldNotSaveException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($this->getErrorMessage());
                $this->logger->critical($e);
            }
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getErrorMessage()
    {
        return __('We can\'t change item right now. Please review the log and try again.');
    }

    /**
     * @param int $collectionSize
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($collectionSize = 0)
    {
        if ($collectionSize) {
            return __('A total of %1 record(s) have been changed.', $collectionSize);
        }

        return __('No records have been changed.');
    }
}
