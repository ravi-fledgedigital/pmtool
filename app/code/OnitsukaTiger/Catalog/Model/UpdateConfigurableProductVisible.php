<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Model;

use Magento\Checkout\Exception;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class HideConfigurableProductNotSalable
 * @package OnitsukaTiger\Catalog\Model
 */
class UpdateConfigurableProductVisible
{

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $_productRepository;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */

    private $isProductSalableForRequestedQty;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    private $logger;

    /**
     * @var ConsoleOutput
     */
    private $output;


    /**
     * HideSkuProduct constructor.
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param IsProductSalableInterface $isProductSalable
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param ConsoleOutput $output
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        IsProductSalableInterface $isProductSalable,
        \OnitsukaTiger\Logger\Logger $logger,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        ConsoleOutput $output
    )
    {
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_productFactory = $productFactory;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->logger = $logger;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->isProductSalable = $isProductSalable;
        $this->output = $output;
    }

    /**
     * Update All Configurable Product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws Exception
     */
    public function updateAllConfigurableProduct()
    {
        $errors = [];
        $collection = $this->getProductConfigurable();
        foreach ($collection as $product) {
            try {
                $this->updateConfigurableProductbySku($product->getSku());
            } catch (\Throwable $e) {
                $errors[] = [
                    'sku' => $product->getSku(),
                    'msg' => $e->getMessage()
                ];
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->output->writeln(sprintf('Error Sku %s : %s ', $error['sku'], $error['msg']));
            }
        }
    }

    /**
     * Get ConfigurableProductBySku and Update
     * @param $sku
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateConfigurableProductbySku($sku)
    {
        $websites = $this->getWebsites();
        $product = $this->_productRepository->get($sku, true);
        if($product->getTypeId()=='configurable'){
            $websitesProduct = $product->getWebsiteIds();
            $checkStockDataWebsite = [];
            $children = $this->getAllProductSimple($product->getSku());
            if ($websites) {
                foreach ($websites as $website) {
                    if($this->checkVisibleSalebleQuantity($children,$website)){
                        $checkStockDataWebsite[] = $website->getId();
                    }
                }
            }
            if(count($checkStockDataWebsite) != count($websitesProduct)) {
                $this->logger->info($product->getSku() . " update from " . json_encode($websitesProduct) . " to " . json_encode($checkStockDataWebsite));
                $this->saveProduct($product,$checkStockDataWebsite);
            }
        }

    }
    /**
     * Get All Configurable Product
     * @return mixed
     */
    private function getProductConfigurable()
    {
        $collection = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect('type_id')
            ->addAttributeToFilter('type_id',array('eq' => 'configurable')
            );
        return $collection;
    }

    /**
     * Get All Simple Product From Sku of Configurable Product
     * @return mixed
     */
    private function getAllProductSimple($sku)
    {
        $collection = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('type_id')
            ->addAttributeToFilter('type_id',array('eq' => 'simple'))
            ->addAttributeToFilter('sku',array('like' => $sku.'%')
            );
        return $collection;
    }
    /**
     * @param $product
     * @param $websites
     */
    private function saveProduct($product,$websites)
    {
        $product->setWebsiteIds($websites);
        $product->save();
    }

    /**
     * @param $products
     * @param $website
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkVisibleSalebleQuantity($products,$website){
        $instock = false;
        foreach ($products as $child){
            $stockData = $this->getStockQty($child->getSku(),$website->getId(),$child);
            if($stockData){
                $instock = true;
                break;
            }
        }
        return $instock;
    }

    /**
     * @param $sku
     * @param $websiteId
     * @param $product
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStockQty($sku,$websiteId,$product)
    {
        $stockId = (int)$this->stockByWebsiteId->execute((int)$websiteId)->getStockId();
        if(!in_array($websiteId,$product->getWebsiteIds())){
            return false;
        }
        $isSalable = $this->isProductSalable->execute($sku, $stockId);
        if (!$isSalable) {
            return false;
        }

        $productSalableResult = $this->isProductSalableForRequestedQty->execute($sku, $stockId, 1);
        if (!$productSalableResult->isSalable() && !$product->isComposite()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    private function getWebsites()
    {
        return $this->_storeManager->getWebsites();
    }

}
