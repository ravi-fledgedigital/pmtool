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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Service\Autocomplete\Resolver;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface
    as FieldNameResolver;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Mirasvit\Sorting\Model\Criterion\ConditionNode;
use Mirasvit\Sorting\Service\Autocomplete\Resolver\FieldResolverInterface;

class AttributeFieldResolver implements FieldResolverInterface
{
    private AttributeProvider $attributeProvider;

    private FieldNameResolver $fieldNameResolver;

    public function __construct(
        AttributeProvider $attributeProvider,
        FieldNameResolver $fieldNameResolver
    ) {
        $this->attributeProvider = $attributeProvider;
        $this->fieldNameResolver = $fieldNameResolver;
    }

    /**
     * @return array{
     *     order: string,
     *     direction: string
     * }
     */
    public function resolveEsField(ConditionNode $criterionNode): array
    {
        $attributeKey = $criterionNode->getAttribute();
        $attribute    = $this->attributeProvider->getByAttributeCode($attributeKey);
        $fieldName    = $this->fieldNameResolver->getFieldName($attribute);

        if ($attribute->isSortable() &&
            !$attribute->isComplexType() &&
            !($attribute->isFloatType() || $attribute->isIntegerType())
        ) {
            $suffix    = $this->fieldNameResolver->getFieldName(
                $attribute,
                ['type' => FieldMapperInterface::TYPE_SORT]
            );
            $fieldName .= '.' . $suffix;
        }

        return [
            'order'     => $fieldName,
            'direction' => $criterionNode->getDirection(),
        ];
    }
}
