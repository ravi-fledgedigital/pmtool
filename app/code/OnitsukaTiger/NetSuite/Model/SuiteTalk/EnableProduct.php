<?php

namespace OnitsukaTiger\NetSuite\Model\SuiteTalk;

class EnableProduct extends \OnitsukaTiger\NetSuite\Model\SuiteTalk
{
    const WEBSITE_ID_SINGAPORE = 'web_sg';
    const WEBSITE_ID_THAILAND = 'web_th';
    const WEBSITE_ID_MALAYSIA = 'web_my';
    const WEBSITE_ID_VIETNAM = 'web_vn';
    const NETSUITE_NAME_SINGAPORE = 'Singapore';
    const NETSUITE_NAME_THAILAND = 'Thailand';
    const NETSUITE_NAME_MALAYSIA = 'Malaysia';
    const NETSUITE_NAME_VIETNAM = 'Vietnam';
    const NETSUITE_ID_SINGAPORE = '3';
    const NETSUITE_ID_THAILAND = '1';
    const NETSUITE_ID_MALAYSIA = '2';
    const NETSUITE_ID_VIETNAM = '4';

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $productAttributeRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var SearchProduct
     */
    protected $searchProduct;

    /**
     * SuiteTalk constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \OnitsukaTiger\Logger\Api\Logger $logger
     * @param \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping
     * @param \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository
     * @param SearchProduct $searchProduct
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \OnitsukaTiger\Shipment\Model\ShipmentStatus $shipment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \OnitsukaTiger\Logger\Api\Logger $logger,
        \OnitsukaTiger\NetSuite\Model\SourceMapping $sourceMapping,
        \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helper,
        \Magento\Framework\Filesystem\DirectoryList  $dir,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
    ) {
        parent::__construct(
            $shipmentRepository,
            $shipment,
            $scopeConfig,
            $orderRepository,
            $orderItemRepository,
            $logger,
            $sourceMapping,
            $helper,
            $dir
        );

        $this->productRepository = $productRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchProduct = $searchProduct;
    }

    /**
     * Update item
     * @param \Magento\Catalog\Model\Product $product
     * @throws \Magento\Framework\Exception\InputException
     */
    public function updateItem(
        \Magento\Catalog\Model\Product $product
    ) {
        if (!$this->scopeConfig->getValue('netsuite/suitetalk/product_enable_disable')) {
            $this->logger->info('Prodcut enable / disable sync is disable');
            return;
        }

        $internalId = $product->getNetsuiteInternalId();
        if (!$internalId) {
            $msg = sprintf('SKU %s does not have netsuite_internal_id', $product->getSku());
            $this->logger->error($msg);
            return;
        }

        $service = $this->getService();

        $field = new \NetSuite\Classes\MultiSelectCustomFieldRef();
        $field->scriptId = self::SCRIPT_ID_CUSTITEM_ECOMM_PRODUCT;

        $list = [];
        $attributes = $product->getAttributeText('netsuite_enable');
        if (!$attributes) {
            $attributes = [];
        }
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }
        foreach ($attributes as $websiteId) {
            $value = new \NetSuite\Classes\ListOrRecordRef();
            switch ($websiteId) {
                case self::WEBSITE_ID_SINGAPORE:
                    $value->name = self::NETSUITE_NAME_SINGAPORE;
                    $value->internalId = self::NETSUITE_ID_SINGAPORE;
                    break;
                case self::WEBSITE_ID_THAILAND:
                    $value->name = self::NETSUITE_NAME_THAILAND;
                    $value->internalId = self::NETSUITE_ID_THAILAND;
                    break;
                case self::WEBSITE_ID_MALAYSIA:
                    $value->name = self::NETSUITE_NAME_MALAYSIA;
                    $value->internalId = self::NETSUITE_ID_MALAYSIA;
                    break;
                case self::WEBSITE_ID_VIETNAM:
                    $value->name = self::NETSUITE_NAME_VIETNAM;
                    $value->internalId = self::NETSUITE_ID_VIETNAM;
                    break;
            }
            $value->typeId = "533";
            $list[] = $value;
        }

        if (count($list)) {
            $field->value = $list;
        }

        $fields = new \NetSuite\Classes\CustomFieldList();
        $fields->customField = [$field];

        $item = new \NetSuite\Classes\InventoryItem();
        $item->internalId = $internalId;
        $item->customFieldList = $fields;

        $updateRequest = new \NetSuite\Classes\UpdateRequest();
        $updateRequest->record = $item;
        $updateResponse = $service->update($updateRequest);

        /** @var \NetSuite\Classes\WriteResponse $result */
        $result = $updateResponse->writeResponse;

        if (!$result->status->isSuccess) {
            $msg = sprintf('Failed API call : %s', $result->status->statusDetail);
            $this->logger->error($msg);
            throw new \Magento\Framework\Exception\InputException($msg);
        } else {
            $this->logger->info(sprintf('SKU %s has been updated to %s successfully', $product->getSku(), json_encode($attributes)));
        }

    }

    /**
     * Get Magento <-> NS attribute map
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttributeMap(): array
    {
        $attributes = [];
        $codes = [
            self::WEBSITE_ID_THAILAND => self::NETSUITE_ID_THAILAND,
            self::WEBSITE_ID_MALAYSIA => self::NETSUITE_ID_MALAYSIA,
            self::WEBSITE_ID_SINGAPORE => self::NETSUITE_ID_SINGAPORE,
            self::WEBSITE_ID_VIETNAM => self::NETSUITE_ID_VIETNAM
        ];
        $attribute = $this->productAttributeRepository->get('netsuite_enable');
        $options = $attribute->getOptions();
        foreach ($options as $option) {
            $label = $option->getLabel();
            if (array_key_exists($label, $codes)) {
                $code = $codes[$label];
                $attributes[$code] = $option->getValue();
            }
        }
        return $attributes;
    }

    /**
     * @param string $sku
     * @param bool $th
     * @param bool $my
     * @param bool $sg
     * @param bool $vn
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function mergeNetSuiteValue($sku, $th, $my, $sg, $vn)
    {
        // update using NS information if value is NULL
        if (!$th || !$my || !$sg || !$vn) {
            $values = $this->searchProduct->searchEnableFlagBySku($sku);
            $ret = [];
            foreach ($values as $val) {
                $ret[] = $val->internalId;
            }

            if (!$th) {
                $th = in_array(self::NETSUITE_ID_THAILAND, $ret) ? 1 : 0;
                $this->logger->info(sprintf('SKU : [%s] thailand set to %s', $sku, $th));
            }
            if (!$my) {
                $my = in_array(self::NETSUITE_ID_MALAYSIA, $ret) ? 1 : 0;
                $this->logger->info(sprintf('SKU : [%s] malaysia set to %s', $sku, $my));
            }
            if (!$sg) {
                $sg = in_array(self::NETSUITE_ID_SINGAPORE, $ret) ? 1 : 0;
                $this->logger->info(sprintf('SKU : [%s] singapore set to %s', $sku, $sg));
            }
            if (!$vn) {
                $vn = in_array(self::NETSUITE_ID_VIETNAM, $ret) ? 1 : 0;
                $this->logger->info(sprintf('SKU : [%s] vietnam set to %s', $sku, $vn));
            }
        }

        // update product
        $attributes = $this->getAttributeMap();
        $flags = [];
        if ($th) {
            $flags[] = $attributes[self::NETSUITE_ID_THAILAND];
        }
        if ($my) {
            $flags[] = $attributes[self::NETSUITE_ID_MALAYSIA];
        }
        if ($sg) {
            $flags[] = $attributes[self::NETSUITE_ID_SINGAPORE];
        }
        if ($vn) {
            $flags[] = $attributes[self::NETSUITE_ID_VIETNAM];
        }
        $product = $this->productRepository->get($sku);
        $product->setData('netsuite_enable', implode(',', $flags));
        $this->productRepository->save($product);

        return $product;
    }
}
