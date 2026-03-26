<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Condition;

use Amasty\Rma\Controller\Adminhtml\AbstractCondition;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ConditionRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends AbstractCondition
{
    /**
     * @var ConditionRepositoryInterface
     */
    private $repository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        ConditionRepositoryInterface $repository,
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            try {
                $conditionId = 0;
                if ($conditionId = (int)$this->getRequest()->getParam(RegistryConstants::CONDITION_ID)) {
                    $model = $this->repository->getById($conditionId);
                } else {
                    /** @var \Amasty\Rma\Model\Condition\Condition $model */
                    $model = $this->repository->getEmptyConditionModel();
                }

                $stores = [];
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                //TODO do it in repository
                foreach ($storeIds as $storeId) {
                    /** @var \Amasty\Rma\Model\Condition\ConditionStore $conditionStore */
                    $conditionStore = $this->repository->getEmptyConditionStoreModel();
                    $stores[] = $conditionStore->setStoreId((int)$storeId)
                        ->setLabel((!empty($data['storelabel' . $storeId]) ? $data['storelabel' . $storeId] : ''));
                }
                $model->setStores($stores);

                $model->addData($data);
                $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::CONDITION_ID => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set(RegistryConstants::CONDITION_DATA, $data);
                if ($conditionId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::CONDITION_ID => $conditionId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
