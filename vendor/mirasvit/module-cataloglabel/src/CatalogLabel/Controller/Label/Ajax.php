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


namespace Mirasvit\CatalogLabel\Controller\Label;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\BlockFactory;
use Mirasvit\CatalogLabel\Block\Product\Label\Placeholder;

class Ajax extends Action
{
    private $blockFactory;

    private $productRepository;

    public function __construct(BlockFactory $blockFactory,  ProductRepository $productRepository, Context $context)
    {
        $this->blockFactory      = $blockFactory;
        $this->productRepository = $productRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = [];

        $productData = $this->getRequest()->getParam('productData');

        foreach ($productData as $productId => $config) {
            $type = $config['type'] ?? 'list';

            /** @var ProductInterface $parentProduct */
            $product = $parentProduct = $this->productRepository->getById($productId);

            if ($parentProduct->getTypeId() != 'configurable') {
                continue;
            }

            $result[$productId] = [];

            if (isset($config['swatches']) && count($config['swatches'])) {
                $collection = $parentProduct->getTypeInstance()->getUsedProductCollection($parentProduct);

                foreach ($config['swatches'] as $attrCode => $value) {
                    $collection->addAttributeToFilter($attrCode, $value);
                }

                $product = $this->productRepository->getById($collection->getFirstItem()->getId());
            }

            foreach ($config['placeholders'] as $placeholderCode) {
                /** @var Placeholder $block */
                $block = $this->blockFactory->createBlock(Placeholder::class);

                $block->setPlaceholderByCode($placeholderCode)->setType($type)->setProduct($product);

                $result[$productId][$placeholderCode] = $block->toHtml();
            }
        }

        $response = $this->getResponse();
        $response->representJson(\Mirasvit\Core\Service\SerializeService::encode(['blocks' => $result]));
    }
}
