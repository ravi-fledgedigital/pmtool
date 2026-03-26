<?php

namespace OnitsukaTiger\ConfigurableProduct\Plugin\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as TypeConfigurable;

class Configurable
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Configurable constructor.
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository,
    ) {
        $this->json = $json;
        $this->_productRepository = $_productRepository;
    }

    /**
     * Modify the product json config
     *
     * @param TypeConfigurable $subject
     * @param obj $result
     * @return string
     */
    public function afterGetJsonConfig(
        TypeConfigurable $subject,
        $result
    ) {
        $result = $this->json->unserialize($result);
        $result['productAdditionalData'] = [];//$this->productAdditionalData($subject);
        //$fullActionName = $subject->getRequest()->getFullActionName();
        $result['productSizeForDisplay'] = [];/*($fullActionName == 'catalog_product_view') ? $this->getSizeForDisplayProduct($subject) : [];*/
        return $this->json->serialize($result);
    }

    /**
     * Get product color code for swatches
     *
     * @param obj $subject
     * @return array
     */
    private function productAdditionalData($subject)
    {
        $data = [];

        $defaultMaterialCode = $subject->getProduct()->getSku();
        foreach ($subject->getAllowProducts() as $product) {
            $materialCode = $product->getMaterialCode() ?: $defaultMaterialCode;
            $data[$product->getId()] = [
                'sku' => $product->getSku(),
                'colorCode' => $materialCode
            ];
        }

        return $data;
    }

    /**
     * Get size for display attributes of the product for the sizes
     *
     * @param obj $subject
     * @return array
     */
    private function getSizeForDisplayProduct($subject)
    {
        $data = [];

        foreach ($subject->getAllowProducts() as $product) {
            $productObj = $this->_productRepository->getById($product->getId());
            $sizeForDisplay = $productObj->getSizeForDisplay();
            $data[$productObj->getQaSize()] = $sizeForDisplay;
        }

        return $data;
    }
}
