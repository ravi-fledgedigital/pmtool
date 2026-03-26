<?php
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Block\Adminhtml\Product;


class Details extends \Magento\Backend\Block\Template
{

    protected $productRepository;
    protected $priceCurrency;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct(){
        $storeId = $this->getRequest()->getParam('store_id', false);
        if ($storeId) {
            return $this->productRepository->getById($this->getRequest()->getParam('id'), false, $storeId);
        }
        return $this->productRepository->getById($this->getRequest()->getParam('id'));
    }

    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
