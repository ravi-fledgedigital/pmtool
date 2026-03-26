<?php

namespace OnitsukaTiger\Catalog\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Vaimo\OTScene7Integration\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Thumbnail extends \Magento\Catalog\Ui\Component\Listing\Columns\Thumbnail
{
    /**
     * @var Data
     */
    private $scene7Helper;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param Data $scene7Helper
     * @param ProductRepositoryInterface $productRepository
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        Data                            $scene7Helper,
        ProductRepositoryInterface      $productRepository,
        ContextInterface                $context,
        UiComponentFactory              $uiComponentFactory,
        \Magento\Catalog\Helper\Image   $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $imageHelper,
            $urlBuilder,
            $components,
            $data
        );
        $this->scene7Helper = $scene7Helper;
        $this->productRepository = $productRepository;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $product = $this->productRepository->getById($item['entity_id']);
                $imageHelper = $this->scene7Helper->getOrderItemProductImage($product, 'order_item_image_thumbnail_admin');
                $item[$fieldName . '_src'] = $imageHelper;
                $item[$fieldName . '_orig_src'] = $imageHelper;
            }
        }

        return $dataSource;
    }
}
