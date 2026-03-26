<?php

namespace OnitsukaTiger\Restock\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class Create Data
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    public $productIdsArr;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
    }

    /**
     * @return bool
     */
    public function isRestock()
    {
        $isEnabled = $this->getConfigValue("onitsukaTiger/general/enable");
        return (bool) $isEnabled;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $sku
     * @return url|sting
     */
    public function getParentUrlBySku($sku)
    {
        $skuArr = explode('.', $sku);
        $parentProductUrl = '';

        if(!empty($skuArr)){
            if(isset($skuArr[0]) && $skuArr[1]){
                $parentSku = $skuArr[0].'.'.$skuArr[1];
                $product = $this->productRepository->get($parentSku);
                if($product){
                    $parentProductUrl = $product->getProductUrl();
                }
            }
        }

        return $parentProductUrl;
    }

    /**
     * get product by id
     * @param int $productId
     * @return obj
     */
    public function getProductById($productId)
    {
        $product = $this->productRepository->getById($productId);
        return $product;
    }
}
