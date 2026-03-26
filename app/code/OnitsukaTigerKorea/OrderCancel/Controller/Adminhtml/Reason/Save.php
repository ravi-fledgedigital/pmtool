<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\Reason;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\ReasonStore;

class Save extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
     */
    private ReasonRepositoryInterface $repository;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param ReasonRepositoryInterface $repository
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     */
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
                if ($reasonId = (int)$this->getRequest()->getParam('reason_id')) {
                    $model = $this->repository->getById($reasonId);
                } else {
                    /** @var Reason $model */
                    $model = $this->repository->getEmptyReasonModel();
                }

                $stores = [];
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                //TODO do it in repository
                foreach ($storeIds as $storeId) {
                    /** @var ReasonStore $reasonStore */
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
                        ['reason_id' => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set('reason_data', $data);
                if ($reasonId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        ['reason_id' => $reasonId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
