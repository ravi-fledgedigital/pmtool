<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Resolution;

use Amasty\Rma\Controller\Adminhtml\AbstractResolution;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends AbstractResolution
{
    /**
     * @var ResolutionRepositoryInterface
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
        ResolutionRepositoryInterface $repository,
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
                $resolutionId = 0;
                if ($resolutionId = (int)$this->getRequest()->getParam(RegistryConstants::RESOLUTION_ID)) {
                    $model = $this->repository->getById($resolutionId);
                } else {
                    /** @var \Amasty\Rma\Model\Resolution\Resolution $model */
                    $model = $this->repository->getEmptyResolutionModel();
                }

                $stores = [];
                $storeIds = [0];
                foreach ($this->storeManager->getStores() as $store) {
                    $storeIds[] = $store->getId();
                }
                //TODO do it in repository
                foreach ($storeIds as $storeId) {
                    /** @var \Amasty\Rma\Model\Resolution\ResolutionStore $resolutionStore */
                    $resolutionStore = $this->repository->getEmptyResolutionStoreModel();
                    $stores[] = $resolutionStore->setStoreId((int)$storeId)
                        ->setLabel((!empty($data['storelabel' . $storeId]) ? $data['storelabel' . $storeId] : ''));
                }
                $model->setStores($stores);

                $model->addData($data);
                $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::RESOLUTION_ID => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set(RegistryConstants::RESOLUTION_DATA, $data);
                if ($resolutionId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        [RegistryConstants::RESOLUTION_ID => $resolutionId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
