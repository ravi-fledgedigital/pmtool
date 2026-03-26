<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request\ShippingLabel;

use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\FileUpload;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Delete extends Action
{
    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Action\Context $context,
        FileUpload $fileUpload,
        Repository $repository
    ) {
        parent::__construct($context);
        $this->fileUpload = $fileUpload;
        $this->repository = $repository;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
    }

    public function execute()
    {
        if (!$requestId = $this->getRequest()->getParam('request_id')) {
            return null;
        }

        $request = $this->repository->getById($requestId);

        if (!$shippingLabel = $request->getShippingLabel()) {
            return null;
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result = $this->fileUpload->deleteShippingLabel($shippingLabel, $requestId);

        if ($result) {
            $request->setShippingLabel(null);
            $this->repository->save($request);
            $this->eventManager->dispatch(RmaEventNames::SHIPPING_LABEL_DELETED_BY_MANAGER, ['request' => $request]);

            return $resultJson->setData(['deleted' => true]);
        } else {
            return $resultJson->setData(['deleted' => false]);
        }
    }
}
