<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\DataProvider;

use Amasty\Rma\Api\CreateReturnProcessorInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\Condition as ConditionOptions;
use Amasty\Rma\Model\OptionSource\NoReturnableReasons;
use Amasty\Rma\Model\OptionSource\Reason as ReasonOptions;
use Amasty\Rma\Model\OptionSource\Resolution as ResolutionOptions;
use Amasty\Rma\Model\Order\OrderItemImage;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\RequestInterface as HttpRequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class CreateForm extends AbstractDataProvider
{
    /**
     * @var CreateReturnProcessorInterface
     */
    private $createReturnProcessor;

    /**
     * @var HttpRequestInterface
     */
    private $request;

    /**
     * @var \Amasty\Rma\Api\Data\ReturnOrderInterface
     */
    private $returnOrder;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var AddressRenderer
     */
    private $addressRenderer;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var OrderItemImage
     */
    private $orderItemImage;

    /**
     * @var ConditionOptions
     */
    private $conditionOptions;

    /**
     * @var ReasonOptions
     */
    private $reasonOptions;

    /**
     * @var ResolutionOptions
     */
    private $resolutionOptions;

    public function __construct(
        CreateReturnProcessorInterface $createReturnProcessor,
        HttpRequestInterface $request,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        UrlInterface $url,
        GroupRepositoryInterface $groupRepository,
        AddressRenderer $addressRenderer,
        ConfigProvider $configProvider,
        ProductRepositoryInterface $productRepository,
        OrderItemImage $orderItemImage,
        ConditionOptions $conditionOptions,
        ReasonOptions $reasonOptions,
        ResolutionOptions $resolutionOptions,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->createReturnProcessor = $createReturnProcessor;
        $this->request = $request;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->addressRenderer = $addressRenderer;
        $this->groupRepository = $groupRepository;
        $this->productRepository = $productRepository;
        $this->orderItemImage = $orderItemImage;
        $this->conditionOptions = $conditionOptions;
        $this->reasonOptions = $reasonOptions;
        $this->resolutionOptions = $resolutionOptions;
    }

    public function getData()
    {
        $order = $this->returnOrder->getOrder();
        $orderStore = $this->storeManager->getStore($order->getStoreId());
        $data[null]['return_items'] = [];

        foreach ($this->returnOrder->getItems() as $item) {
            $tempItem = clone $item;
            $itemData = $tempItem->getData();
            unset($itemData['product_item']);
            unset($itemData['item']);

            $itemData = [
                RequestItemInterface::REQUEST_ITEM_ID => $item->getItem()->getItemId(),
                'name' => $item->getItem()->getName(),
                'sku' => $item->getItem()->getSku(),
                'url' => $this->url->getUrl(
                    'catalog/product/edit',
                    ['id' => $item->getItem()->getProductId()]
                ),
                'shipped_qty' => (double)$item->getItem()->getQtyShipped(),
                'refunded_qty' => (double)$item->getItem()->getQtyRefunded(),
                RequestItemInterface::REQUEST_QTY => (double)$item->getAvailableQty(),
                RequestItemInterface::QTY =>  (double)$item->getAvailableQty(),
                RequestItemInterface::ORDER_ITEM_ID => $item->getItem()->getItemId(),
                RequestItemInterface::ITEM_STATUS  => 0,
                'is_decimal' => (bool)$item->getItem()->getIsQtyDecimal(),
                'image' => $this->orderItemImage->getUrl($item->getItem()->getItemId()),
                'is_returnable' => $item->isReturnable(),
                'no_returnable_reason' => $item->getNoReturnableReason(),
                RequestItemInterface::CONDITION_ID => '',
                RequestItemInterface::REASON_ID => '',
                RequestItemInterface::RESOLUTION_ID => ''
            ];

            if (!$item->isReturnable() && ($item->getNoReturnableReason() === NoReturnableReasons::ALREADY_RETURNED)) {
                $itemData['previous_requests'] = [];

                foreach ($item->getNoReturnableData() as $request) {
                    $itemData['previous_requests'][] = [
                        'url' => $this->url->getUrl(
                            'amrma/request/view',
                            [RegistryConstants::REQUEST_ID => $request[RequestInterface::REQUEST_ID]]
                        ),
                        'label' => '#' . str_pad($request[RequestInterface::REQUEST_ID], 9, '0', STR_PAD_LEFT)
                    ];
                }
            }

            $data[null]['return_items'][][] = $itemData;
        }

        if ($order->getCustomerGroupId()) {
            try {
                $customerGroup = $this->groupRepository->getById($order->getCustomerGroupId())->getCode();
            } catch (NoSuchEntityException $e) {
                $customerGroup = __('Customer group with specified ID %1 not found.', $order->getCustomerGroupId());
            }
        } else {
            $customerGroup = 'NOT LOGGED IN';
        }

        $data[null][RequestInterface::ORDER_ID] = $order->getEntityId();
        $data[null]['information'] = [
            'order' => [
                'entity_id' => $order->getEntityId(),
                'increment_id' => '#' . $order->getIncrementId(),
                'created' => $order->getCreatedAt(),
                'status' => $order->getStatus(),
                'link' => $this->url->getUrl(
                    'sales/order/view',
                    ['order_id' => $order->getEntityId()]
                ),
                'store' => [
                    'website' => $this->storeManager->getWebsite($orderStore->getWebsiteId())->getName(),
                    'group' => $this->storeManager->getGroup($orderStore->getStoreGroupId())->getName(),
                    'view' => $orderStore->getName()
                ]
            ],
            'customer' => [
                'name' => $order->getBillingAddress()->getFirstname() . ' '
                    . $order->getBillingAddress()->getLastname(),
                'address' => $this->addressRenderer->format($order->getBillingAddress(), 'html'),
                'email' => $order->getBillingAddress()->getEmail(),
                'customer_group' => $customerGroup
            ]
        ];

        return $data;
    }

    public function getMeta()
    {
        $meta = parent::getMeta();
        $this->returnOrder = $this->createReturnProcessor->process($this->request->getParam('order_id'), true);
        $storeId = $this->returnOrder->getOrder()->getStoreId();

        if ($customFields = $this->configProvider->getCustomFields($storeId)) {
            $meta['rma_details']['children']['custom_fields']['arguments']['data']['config']['label'] =
                $this->configProvider->getCustomFieldsLabel($storeId);

            foreach ($customFields as $code => $label) {
                $meta['rma_details']['children']['custom_fields']['children']
                ['custom_fields.' . $code]['arguments']['data']['config'] = [
                    'label' => $label,
                    'dataType' => 'text',
                    'formElement' => 'input',
                    'componentType' => 'field',
                    'source' => 'custom_fields.' . $code
                ];
            }
        }

        //Items To Return Meta
        $meta['rma_return_order']['arguments']['data']['config']['header'] = [
            __('Product'),
            __('Return Reason'),
            __('Item Condition'),
            __('Resolution'),
            __('Who Pays for Shipping'),
            __('Shipped QTY'),
            __('Refunded QTY'),
            __('Return QTY'),
            __('Approved')
        ];
        $meta['rma_return_order']['arguments']['data']['config']['resolutions'] =
            $this->resolutionOptions->toOptionArray();
        $meta['rma_return_order']['arguments']['data']['config']['conditions'] =
            $this->conditionOptions->toOptionArray();
        $meta['rma_return_order']['arguments']['data']['config']['reasons'] =
            $this->reasonOptions->toOptionArray();

        return $meta;
    }
}
