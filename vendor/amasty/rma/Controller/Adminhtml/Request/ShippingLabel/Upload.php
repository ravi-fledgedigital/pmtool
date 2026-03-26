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
use Magento\Framework\View\Asset\Repository as AssetRepository;

class Upload extends Action
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
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    public function __construct(
        FileUpload $fileUpload,
        Repository $repository,
        AssetRepository $assetRepository,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->fileUpload = $fileUpload;
        $this->repository = $repository;
        $this->assetRepository = $assetRepository;
        $this->eventManager = $context->getEventManager() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);
    }

    public function execute()
    {
        $files = $this->getRequest()->getFiles()->toArray();
        $id = $this->getRequest()->getParam('request_id');

        if (!$files || !$id) {
            return null;
        }

        $request = $this->repository->getById($id);

        if ($shippingLabel = $request->getShippingLabel()) {
            $this->fileUpload->deleteShippingLabel($shippingLabel, $request->getRequestId());
        }

        $result = $this->fileUpload->uploadShippingLabel(array_shift($files), $request->getRequestId());
        $request->setShippingLabel($result['file']);
        $this->repository->save($request);
        $this->eventManager->dispatch(RmaEventNames::SHIPPING_LABEL_ADDED_BY_MANAGER, ['request' => $request]);
        $result['previewUrl'] = $this->assetRepository->getUrl('Amasty_Rma::images/shipping.png');

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
