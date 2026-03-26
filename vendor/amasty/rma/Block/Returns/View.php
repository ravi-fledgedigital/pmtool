<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Returns;

use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\ItemStatus;
use Amasty\Rma\Model\Order\OrderItemImage;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;

class View extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var Renderer
     */
    private $addressRenderer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;

    /**
     * @var ConditionRepositoryInterface
     */
    private $conditionRepository;

    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * @var CollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var FilterProvider
     */
    private $filterProvider;

    /**
     * @var ItemStatus
     */
    private $itemStatus;

    /**
     * @var OrderItemImage
     */
    private $orderItemImage;

    public function __construct(
        Registry $registry,
        Template\Context $context,
        StatusRepositoryInterface $statusRepository,
        ReasonRepositoryInterface $reasonRepository,
        ConditionRepositoryInterface $conditionRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        CollectionFactory $orderItemCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ItemStatus $itemStatus,
        OrderItemImage $orderItemImage,
        OrderRepositoryInterface $orderRepository,
        ConfigProvider $configProvider,
        Renderer $addressRenderer,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->statusRepository = $statusRepository;
        $this->addressRenderer = $addressRenderer;
        $this->orderRepository = $orderRepository;
        $this->configProvider = $configProvider;
        $this->isGuest = !empty($data['isGuest']);
        $this->reasonRepository = $reasonRepository;
        $this->conditionRepository = $conditionRepository;
        $this->resolutionRepository = $resolutionRepository;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->productRepository = $productRepository;
        $this->filterProvider = $filterProvider;
        $this->itemStatus = $itemStatus;
        $this->orderItemImage = $orderItemImage;
    }

    /**
     * @return \Amasty\Rma\Api\Data\RequestInterface
     */
    public function getReturnRequest()
    {
        return $this->registry->registry(\Amasty\Rma\Controller\RegistryConstants::REQUEST_VIEW);
    }

    /**
     * @param $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * @param $statusId
     *
     * @return \Amasty\Rma\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStatusModel($statusId)
    {
        return $this->statusRepository->getById($statusId, $this->_storeManager->getStore()->getId());
    }

    /**
     * @param $address
     *
     * @return string|null
     */
    public function getFormatAddress($address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

    public function getCancelUrl()
    {
        if ($this->isGuest) {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/guest/cancel',
                ['request' => $this->getRequest()->getParam('request')]
            );
        } else {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/account/cancel',
                ['request' => $this->getRequest()->getParam('request')]
            );
        }
    }

    public function getProductByOrderItemId($orderItemId)
    {
        $item = $this->orderItemCollectionFactory->create()
            ->addFieldToFilter(OrderItemInterface::ITEM_ID, (int)$orderItemId)
            ->fetchItem();
        try {
            return $this->productRepository->getById($item->getProductId());
        } catch (\Exception $e) {
            return $item;
        }
    }

    public function getOrderItemImageUrl($orderItemId)
    {
        return $this->orderItemImage->getUrl($orderItemId, 'product_base_image');
    }

    /**
     * @return ConfigProvider
     */
    public function getConfig()
    {
        return $this->configProvider;
    }

    /**
     * @param int $itemStatus
     *
     * @return string
     */
    public function getItemStatusText($itemStatus)
    {
        return $this->itemStatus->toArray()[$itemStatus];
    }

    public function getReasonById($reasonId)
    {
        $reasons = $this->reasonRepository->getReasonsByStoreId(
            $this->_storeManager->getStore()->getId(),
            false,
            true
        );

        if (isset($reasons[$reasonId])) {
            return $reasons[$reasonId];
        }

        return false;
    }

    public function getReasonLabel($reasonId)
    {
        if ($reason = $this->getReasonById($reasonId)) {
            return $reason->getLabel();
        }

        return '';
    }

    public function getReasonPayer($reasonId)
    {
        if ($reason = $this->getReasonById($reasonId)) {
            return $reason->getPayer();
        }

        return 1;
    }

    public function getConditionLabel($conditionId)
    {
        $conditions = $this->conditionRepository->getConditionsByStoreId(
            $this->_storeManager->getStore()->getId(),
            false,
            true
        );

        if (isset($conditions[$conditionId])) {
            return $conditions[$conditionId]->getLabel();
        }

        return '';
    }

    public function getResolutionLabel($resolutionId)
    {
        $resolutions = $this->resolutionRepository->getResolutionsByStoreId(
            $this->_storeManager->getStore()->getId(),
            false,
            true
        );

        if (isset($resolutions[$resolutionId])) {
            return $resolutions[$resolutionId]->getLabel();
        }

        return '';
    }

    /**
     * @param string $urlHash
     *
     * @return string
     */
    public function getChatFetchUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/update',
            ['hash' => $urlHash]
        );
    }

    public function getChatSaveUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/save',
            ['hash' => $urlHash]
        );
    }

    public function getChatUploadUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/uploadtemp',
            ['hash' => $urlHash]
        );
    }

    public function getTrackingSaveUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/trackingnumber/save',
            ['hash' => $urlHash]
        );
    }

    public function getChatDeleteUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/deletetemp',
            ['hash' => $urlHash]
        );
    }

    public function getChatDeleteMessageUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/deletemessage',
            ['hash' => $urlHash]
        );
    }

    public function getTrackingRemoveUrl($urlHash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/trackingnumber/remove',
            ['hash' => $urlHash]
        );
    }

    /**
     * @return string
     */
    public function getRateUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/rating/rate'
        );
    }

    /**
     * @param \Amasty\Rma\Api\Data\StatusStoreInterface $statusStore
     *
     * @return string
     * @throws \Exception
     */
    public function getReturnInstruction($statusStore)
    {
        return $this->filterProvider->getPageFilter()->filter($statusStore->getDescription());
    }

    /**
     * @return bool
     */
    public function isChatEnabled()
    {
        return $this->configProvider->isChatEnabled($this->_storeManager->getStore()->getId());
    }
}
