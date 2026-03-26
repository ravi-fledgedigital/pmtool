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

namespace Mirasvit\CatalogLabel\Model\System\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Model\Context;

class Attribute implements ArrayInterface
{
    protected $productAttributeCollectionFactory;

    protected $context;

    public function __construct(
        ProductCollectionFactory $productAttributeCollectionFactory,
        Context $context
    ) {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->context                           = $context;
    }

    public function toOptionArray(): array
    {
        $result = [];

        $productAttributeCollection = $this->productAttributeCollectionFactory->create();
        $productAttributeCollection
            ->addFieldToFilter('frontend_input', ['like' => '%select'])
            ->addFieldToFilter('is_user_defined', 1)
            ->setOrder('frontend_label', 'asc');
        foreach ($productAttributeCollection as $attribute) {
            $result[] = [
                'label' => $attribute->getFrontendLabel().' ['.$attribute->getAttributeCode().']',
                'value' => $attribute->getAttributeId(),
            ];
        }

        return $result;
    }
}
