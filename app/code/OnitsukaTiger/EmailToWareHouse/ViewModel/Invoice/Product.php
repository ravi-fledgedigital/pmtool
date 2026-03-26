<?php
namespace OnitsukaTiger\EmailToWareHouse\ViewModel\Invoice;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Product implements ArgumentInterface
{
    private ProductRepositoryInterface $_productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->_productRepository = $productRepository;
    }

    /**
     * @param $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct($sku): ProductInterface
    {
        return $this->_productRepository->get($sku);
    }
}
