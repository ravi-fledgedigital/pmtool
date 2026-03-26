<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Block\Product;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Helper\Data;
use Mirasvit\CatalogLabel\Model\LabelFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\Index\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;


/**
 * @method string getImageUrl()
 * @method string getWidth()
 * @method string getHeight()
 * @method string getLabel()
 * @method mixed getResizedImageWidth()
 * @method mixed getResizedImageHeight()
 * @method float getRatio()
 * @method string getCustomAttributes()
 */
class Image extends \Magento\Catalog\Block\Product\Image
{
    protected $dataHelper;

    protected $placeholder;

    protected $productRepository;

    protected $request;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Context $context,
        PlaceholderInterface $placeholder,
        Data $dataHelper,
        LabelFactory $labelFactory,
        CollectionFactory $indexCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        if (isset($data['template'])) {
            $this->setTemplate($data['template']);
            unset($data['template']);
        }
        parent::__construct($context, $data);

        $this->productRepository      = $productRepository;
        $this->placeholder            = $placeholder;
        $this->dataHelper             = $dataHelper;
        $this->request                = $context->getRequest();
        $this->labelFactory           = $labelFactory;
        $this->indexCollectionFactory = $indexCollectionFactory;
        $this->storeManager           = $storeManager;
    }

    /**
     * @param string $html
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _afterToHtml($html)
    {
        $product = $this->getProduct();

        if (!$product && $this->getData('product_id')) {
            $product = $this->productRepository->getById($this->getData('product_id'));
        }

        if ($product) {
            $html .= $this->dataHelper->getProductLabelsHtml($product, 'list');
        }

        $this->unsetData('product');

        return $html;
    }
}
