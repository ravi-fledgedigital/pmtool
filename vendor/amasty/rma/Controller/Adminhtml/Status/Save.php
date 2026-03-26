<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Status;

use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Controller\Adminhtml\AbstractStatus;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends AbstractStatus
{
    /**
     * @var StatusRepositoryInterface
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
        StatusRepositoryInterface $repository,
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
                $statusId = 0;
                if ($statusId = (int)$this->getRequest()->getParam(RegistryConstants::STATUS_ID)) {
                    $model = $this->repository->getById($statusId);
                } else {
                    /** @var \Amasty\Rma\Model\Status\Status $model */
                    $model = $this->repository->getEmptyStatusModel();
                }
                $stores = [];
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                foreach ($storeIds as $storeId) {
                    /** @var \Amasty\Rma\Model\Status\StatusStore $statusStore */
                    $statusStore = $this->repository->getEmptyStatusStoreModel();
                    $statusStore->setStoreId((int)$storeId)
                        ->setLabel((!empty($data['storelabel' . $storeId]) ? $data['storelabel' . $storeId] : ''))
                        ->setDescription(
                            (!empty($data['storedescription' . $storeId])
                                ? $data['storedescription' . $storeId]
                                : ''
                            )
                        )->setIsSendEmailToCustomer(!empty($data['send_to_customer' . $storeId]))
                        ->setCustomerCustomText(
                            !empty($data['customer_custom_text' . $storeId])
                                ? $data['customer_custom_text' . $storeId]
                                : ''
                        )->setCustomerEmailTemplate(
                            !empty($data['customer_template' . $storeId])
                                ? (int)$data['customer_template' . $storeId]
                                : 0
                        )->setIsSendEmailToAdmin(!empty($data['send_to_admin' . $storeId]))
                        ->setAdminCustomText(
                            !empty($data['admin_custom_text' . $storeId])
                                ? $data['admin_custom_text' . $storeId]
                                : ''
                        )->setAdminEmailTemplate(
                            !empty($data['admin_template' . $storeId])
                                ? (int)$data['admin_template' . $storeId]
                                : 0
                        )->setIsSendToChat(!empty($data['send_to_chat' . $storeId]))
                        ->setChatMessage(
                            !empty($data['chat_message' . $storeId])
                                ? $data['chat_message' . $storeId]
                                : ''
                        );

                    $stores[] = $statusStore;
                }
                $model->setStores($stores);

                $model->addData($data);
                $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::STATUS_ID => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set(RegistryConstants::STATUS_DATA, $data);
                if ($statusId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::STATUS_ID => $statusId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
