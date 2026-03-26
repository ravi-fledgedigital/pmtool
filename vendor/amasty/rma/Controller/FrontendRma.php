<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller;

use Amasty\Rma\Utils\FileUpload;

class FrontendRma
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Amasty\Rma\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\Rma\Model\Cookie\HashChecker
     */
    private $hashChecker;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Amasty\Rma\Api\ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var \Amasty\Rma\Utils\FileUpload
     */
    private $fileUpload;

    /**
     * @var \Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory
     */
    private $customFieldFactory;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Amasty\Rma\Model\ConfigProvider $configProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\Rma\Api\ChatRepositoryInterface $chatRepository,
        \Amasty\Rma\Model\Cookie\HashChecker $hashChecker,
        \Amasty\Rma\Utils\FileUpload $fileUpload,
        \Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory $customFieldFactory
    ) {
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->hashChecker = $hashChecker;
        $this->storeManager = $storeManager;
        $this->chatRepository = $chatRepository;
        $this->fileUpload = $fileUpload;
        $this->customFieldFactory = $customFieldFactory;
    }

    /**
     * @return string
     */
    public function getReturnRequestHomeUrl()
    {
        if ($customerId = $this->customerSession->getCustomerId()) {
            return $this->configProvider->getUrlPrefix() . '/account/history';
        } elseif ($this->configProvider->isGuestRmaAllowed()) {
            if ($hash = $this->hashChecker->getHash()) {
                return $this->configProvider->getUrlPrefix() . '/guest/history';
            } else {
                return $this->configProvider->getUrlPrefix() . '/guest/login';
            }
        }

        return 'customer/account/login';
    }

    /**
     * @param \Amasty\Rma\Api\CustomerRequestRepositoryInterface $requestRepository
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Framework\App\RequestInterface $httpRequest
     *
     * @return \Amasty\Rma\Api\Data\RequestInterface
     */
    public function processNewRequest($requestRepository, $order, $httpRequest)
    {
        $customFields = [];
        $request = $requestRepository->getEmptyRequestModel();
        foreach ($httpRequest->getParam('custom_fields', []) as $code => $label) {
            $customFields[] = $this->customFieldFactory->create([
                'key' => $code,
                'value' => $label
            ]);
        }
        $request->setStoreId($this->storeManager->getStore()->getId())
            ->setOrderId($order->getEntityId())
            ->setCustomerName(
                $order->getBillingAddress()->getFirstname() . ' '
                . $order->getBillingAddress()->getLastname()
            )->setCustomFields($customFields);

        $request->setCustomerId($this->customerSession->getCustomerId());

        $returnItems = [];
        foreach ($httpRequest->getParam('items') as $itemId => $item) {
            if (empty($item['return']) || empty($item['qty']) || $item['qty'] < 0.0001
                || empty($item['condition']) || empty($item['reason']) || empty($item['resolution'])
            ) {
                continue;
            }

            $returnItems[] = $requestRepository->getEmptyRequestItemModel()
                ->setQty((float)$item['qty'])
                ->setResolutionId((int)$item['resolution'])
                ->setReasonId((int)$item['reason'])
                ->setConditionId((int)$item['condition'])
                ->setOrderItemId((int)$itemId);
        }
        $request->setRequestItems($returnItems);

        return $request;
    }

    /**
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @param string $comment
     * @param array $files
     *
     * @throws \Exception
     */
    public function saveNewReturnMessage($request, $comment, $files)
    {
        $message = $this->chatRepository->getEmptyMessageModel();
        $message->setIsRead(0)
            ->setMessage($comment)
            ->setCustomerId($this->customerSession->getCustomerId())
            ->setName($request->getCustomerName())
            ->setRequestId($request->getRequestId());

        if ($files) {
            $messageFiles = [];
            foreach ($files as $file) {
                $messageFile = $this->chatRepository->getEmptyMessageFileModel();
                $messageFile->setFilepath($file[FileUpload::FILEHASH])
                    ->setFilename($file[FileUpload::FILENAME]);
                $messageFiles[] = $messageFile;
            }
            $message->setMessageFiles($messageFiles);
        }

        try {
            $this->chatRepository->save($message, false);
        } catch (\Exception $e) {
            null;
        }
    }
}
