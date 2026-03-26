<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Reason;

use Amasty\Rma\Controller\Adminhtml\AbstractReason;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
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
        ReasonRepositoryInterface $repository,
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
                $reasonId = 0;
                if ($reasonId = (int)$this->getRequest()->getParam(RegistryConstants::REASON_ID)) {
                    $model = $this->repository->getById($reasonId);
                } else {
                    /** @var \Amasty\Rma\Model\Reason\Reason $model */
                    $model = $this->repository->getEmptyReasonModel();
                }

                $stores = [];
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                //TODO do it in repository
                foreach ($storeIds as $storeId) {
                    /** @var \Amasty\Rma\Model\Reason\ReasonStore $reasonStore */
                    $reasonStore = $this->repository->getEmptyReasonStoreModel();
                    $stores[] = $reasonStore->setStoreId((int)$storeId)
                        ->setLabel((!empty($data['storelabel' . $storeId]) ? $data['storelabel' . $storeId] : ''));
                }
                $model->setStores($stores);

                $model->addData($data);
                $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::REASON_ID => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set(RegistryConstants::REASON_DATA, $data);
                if ($reasonId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::REASON_ID => $reasonId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
