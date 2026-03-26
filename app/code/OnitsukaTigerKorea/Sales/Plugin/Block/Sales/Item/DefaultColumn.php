<?php

namespace OnitsukaTigerKorea\Sales\Plugin\Block\Sales\Item;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class DefaultColumn
{


    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * DefaultColumn constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn $subject
     * @param $result
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function afterGetOrderOptions(\Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn $subject, $result)
    {
        if (!in_array('sales_order_view', $subject->getLayout()->getUpdate()->getHandles())) {
            return $result;
        }

        $options = [];
        $item = $subject->getItem();

        if ($item->getSkuWms()) {
            $options[] = [
                'label' => __('SKU_WMS'),
                'value' => $item->getSkuWms(),
                'custom_view' => true
            ];
        }

        return array_merge($options, $result);
    }
}
