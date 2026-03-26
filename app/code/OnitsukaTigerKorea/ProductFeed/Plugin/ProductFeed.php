<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\ProductFeed\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\ProductFeed\Helper\Data;
use OnitsukaTigerKorea\ProductFeed\Helper\Data as ProductFeedData;

class ProductFeed {

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Configurable
     */
    protected $resourceConfigurable;

    /**
     * @var ProductFeedData
     */
    protected $dataHelper;

    /**
     * ProductFeed constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $resourceConfigurable
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Configurable $resourceConfigurable,
        ProductFeedData $koreaData
    ){
        $this->productRepository = $productRepository;
        $this->resourceConfigurable = $resourceConfigurable;
        $this->dataHelper = $koreaData;
    }

    /**
     * @param Data $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetProductsData(Data $subject, $result){
        $productCollection = $result;
        $storeId = $productCollection->getFirstItem()->getStoreId();
        if ($this->dataHelper->isProductFeedEnabled($storeId)) {
            foreach($productCollection as $product) {
                if($product->getTypeId() == 'configurable') {
                    $mpn =  $product->getSku();
                }else {
                    $idSimple = $product->getEntityId();
                    $SkuConfig = $this->getParentSkuFromChildEntity($idSimple);
                    $mpn = $SkuConfig;
                }
                $mpn = str_replace(".","-", $mpn);
                $product->setData('mpn', $mpn);
            }
        }
        return $productCollection;
    }

    /**
     * @param $id
     * @return string
     * @throws NoSuchEntityException
     */
    protected function  getParentSkuFromChildEntity($id): string
    {
        if ($id) {
            $parentIds = $this->resourceConfigurable->getParentIdsByChild($id);
            if (!empty($parentIds)) {
                $productConfig = $this->productRepository->getById($parentIds[0]);
                return $productConfig->getSku();
            }
        }
        return '';
    }
}
