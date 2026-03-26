<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Returns;

use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Order\OrderItemImage;
use Magento\Cms\Helper\Page;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer as ItemRenderer;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Store\Model\StoreManagerInterface;

class NewReturn extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var AddressRenderer
     */
    private $addressRenderer;

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
     * @var Page
     */
    private $pageHelper;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var OrderItemImage
     */
    private $orderItemImage;

    /**
     * @var ItemRenderer
     */
    private $itemRenderer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ConfigProvider $configProvider,
        Registry $registry,
        AddressRenderer $addressRenderer,
        ReasonRepositoryInterface $reasonRepository,
        ConditionRepositoryInterface $conditionRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        Page $pageHelper,
        ItemRenderer $itemRenderer,
        OrderItemImage $orderItemImage,
        Template\Context $context,
        array $data = [],
        ?StoreManagerInterface $storeManager = null // TODO move to not optional
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->registry = $registry;
        $this->addressRenderer = $addressRenderer;
        $this->reasonRepository = $reasonRepository;
        $this->conditionRepository = $conditionRepository;
        $this->resolutionRepository = $resolutionRepository;
        $this->pageHelper = $pageHelper;
        $this->isGuest = !empty($data['isGuest']);
        $this->orderItemImage = $orderItemImage;
        $this->itemRenderer = $itemRenderer;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * @return \Amasty\Rma\Api\Data\ReturnOrderInterface
     */
    public function getReturnOrder()
    {
        return $this->registry->registry(\Amasty\Rma\Controller\RegistryConstants::CREATE_RETURN_ORDER);
    }

    /**
     * @return string
     */
    public function getHistoryUrl()
    {
        if ($this->isGuest) {
            return $this->getUrl($this->configProvider->getUrlPrefix() . '/guest/history');
        } else {
            return $this->getUrl($this->configProvider->getUrlPrefix() . '/account/history');
        }
    }

    /**
     * @param $address
     *
     * @return string|null
     */
    public function getFormatAddress($address)
    {
        $currentStore = $this->storeManager->getStore();
        $addressHtml = $this->addressRenderer->format($address, 'html');
        $this->storeManager->setCurrentStore($currentStore);

        return $addressHtml;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     *
     * @return string
     */
    public function getProductImage($orderItemId)
    {
        return $this->orderItemImage->getUrl($orderItemId, 'product_base_image');
    }

    /**
     * @param mixed $option
     *
     * @return array
     */
    public function getOptionValue($option)
    {
        return $this->itemRenderer->getFormatedOptionValue($option);
    }

    /**
     * @param Amasty\Rma\Model\Order\ReturnOrderItem $item
     *
     * @return array
     */
    public function getAdditionalData($item)
    {
        $orderItem = $item->getItem();

        if ($orderItem->getParentItemId() !== null) {
            $parent = $orderItem->getParentItem();

            return $this->itemRenderer->setItem($parent)->getItemOptions();
        }

        return $this->itemRenderer->setItem($orderItem)->getItemOptions();
    }

    /**
     * @return \Amasty\Rma\Api\Data\ReasonInterface[]
     */
    public function getReasons()
    {
        return $this->reasonRepository->getReasonsByStoreId($this->_storeManager->getStore()->getId());
    }

    /**
     * @return \Amasty\Rma\Api\Data\ConditionInterface[]
     */
    public function getConditions()
    {
        return $this->conditionRepository->getConditionsByStoreId($this->_storeManager->getStore()->getId());
    }

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionInterface[]
     */
    public function getResolutions()
    {
        return $this->resolutionRepository->getResolutionsByStoreId($this->_storeManager->getStore()->getId());
    }

    /**
     * @return ConfigProvider
     */
    public function getConfig()
    {
        return $this->configProvider;
    }

    /**
     * @return string
     */
    public function getPolicyUrl()
    {
        return $this->pageHelper->getPageUrl($this->configProvider->getReturnPolicyPage());
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        if ($this->isGuest) {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() .'/guest/save',
                ['secret' => $this->getRequest()->getParam('secret')]
            );
        }

        return $this->_urlBuilder->getUrl($this->configProvider->getUrlPrefix() . '/account/save');
    }

    public function getChatUploadUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/uploadtemp'
        );
    }

    public function getChatDeleteUrl()
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/chat/deletetemp'
        );
    }

    /**
     * @param array $request
     *
     * @return string
     */
    public function getRequestViewUrl($request)
    {
        if ($this->isGuest) {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() .'/guest/view',
                ['request' => $request[RequestInterface::URL_HASH]]
            );
        } else {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() .'/account/view',
                ['request' => $request[RequestInterface::REQUEST_ID]]
            );
        }
    }

    public function getConfirmationMessage(): string
    {
        return nl2br($this->configProvider->getConfirmationMessage());
    }
}
