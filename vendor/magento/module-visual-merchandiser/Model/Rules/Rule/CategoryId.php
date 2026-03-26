<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

use Magento\Framework\Exception\NoSuchEntityException;

class CategoryId extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @var array
     */
    private const OPERATOR_MAP = [
        'eq' => 'in',
        'neq' => 'nin',
    ];

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param array $rule
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        $rule,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($rule, $attribute);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritdoc
     */
    public function applyToCollection($collection)
    {
        $categoryIds = $this->_rule['value'];
        $categoryIds = explode(',', $categoryIds);
        $categoryIds = array_map('trim', $categoryIds);
        $productsIds = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
            } catch (NoSuchEntityException $e) {
                $this->notices[] = __('Category ID \'%1\' not found', $categoryId);
                continue;
            }
            $productsIds[] = array_keys($category->getProductsPosition());
        }
        $productsIds = array_unique(array_merge([], ...$productsIds));
        $collection->addFieldToFilter('entity_id', [
            self::OPERATOR_MAP[$this->_rule['operator']] => $productsIds
        ]);
    }

    /**
     * @inheritdoc
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function getOperators()
    {
        return [
            'eq' => __('Equal'),
            'neq' => __('Not equal'),
        ];
    }
}
