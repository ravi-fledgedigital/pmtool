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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Service;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

/**
 * Resolves grouped option codes to actual option IDs for products.
 *
 * This service is shared between plugins to ensure consistent option selection
 * for both product images and swatch preselection.
 */
class GroupedOptionResolver
{
    private $request;

    private $groupRepository;

    /** @var array<string, array|null> */
    private $groupCache = [];

    /** @var array<int, array<string, string>> */
    private $resolvedOptionsCache = [];

    /** @var array|null */
    private $originalParams = null;

    public function __construct(
        RequestInterface $request,
        GroupRepository $groupRepository
    ) {
        $this->request         = $request;
        $this->groupRepository = $groupRepository;
    }

    public function resolve(ProductModel $product): array
    {
        $productId = (int)$product->getId();

        if (isset($this->resolvedOptionsCache[$productId])) {
            return $this->resolvedOptionsCache[$productId];
        }

        $resolved = [];

        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            $this->resolvedOptionsCache[$productId] = $resolved;
            return $resolved;
        }

        $params = $this->getOriginalParams();

        if (empty($params)) {
            $this->resolvedOptionsCache[$productId] = $resolved;
            return $resolved;
        }

        $productOptionIds = $this->getProductConfigurableOptionIds($product);

        foreach ($params as $code => $value) {
            if (!is_string($value)) {
                continue;
            }

            // Handle comma-separated values (multiple filters for same attribute)
            $values = explode(',', $value);

            foreach ($values as $singleValue) {
                $singleValue = trim($singleValue);
                if ($singleValue === '') {
                    continue;
                }

                $groupData = $this->getGroupData($singleValue);

                if (!$groupData) {
                    continue;
                }

                $attributeCode = $groupData['attributeCode'];
                $groupOptionIds = $groupData['optionIds'];

                if (empty($groupOptionIds) || !isset($productOptionIds[$attributeCode])) {
                    continue;
                }

                $matchingOptionId = $this->findFirstMatchingOptionId(
                    $groupOptionIds,
                    $productOptionIds[$attributeCode]
                );

                if ($matchingOptionId !== null) {
                    $resolved[$attributeCode] = $matchingOptionId;
                    break;
                }
            }
        }

        $this->resolvedOptionsCache[$productId] = $resolved;

        return $resolved;
    }

    /**
     * Get resolved option ID for a specific attribute.
     */
    public function getResolvedOptionId(ProductModel $product, string $attributeCode): ?string
    {
        $resolved = $this->resolve($product);

        return $resolved[$attributeCode] ?? null;
    }

    /**
     * Check if param value is a grouped option code.
     */
    public function isGroupedOption(string $value): bool
    {
        return $this->getGroupData($value) !== null;
    }

    private function getOriginalParams(): array
    {
        if ($this->originalParams !== null) {
            return $this->originalParams;
        }

        $queryString = $this->request->getQuery()->toArray();

        $systemParams = [
            'isAjax', 'is_ajax', 'is_scroll', 'scrollAjax', 'ajax', 'mode', '_', 'form_key', 'uenc',
            'p', 'page', 'product_list_order', 'product_list_dir', 'product_list_mode', 'product_list_limit',
            'q', 'cat', 'price', 'dir', 'order', 'limit', 'toolbar_state'
        ];

        $filterQueryParams = array_diff_key($queryString, array_flip($systemParams));

        $this->originalParams = !empty($filterQueryParams) ? $queryString : $this->request->getParams();

        return $this->originalParams;
    }

    private function getGroupData(string $code): ?array
    {
        if (array_key_exists($code, $this->groupCache)) {
            return $this->groupCache[$code];
        }

        $group = $this->groupRepository->getByCode($code);

        if (!$group) {
            $this->groupCache[$code] = null;
            return null;
        }

        $optionIds = array_values(array_filter(
            array_map('trim', $group->getAttributeValueIds()),
            function ($val) {
                return $val !== '' && $val !== null;
            }
        ));

        $this->groupCache[$code] = [
            'attributeCode' => $group->getAttributeCode(),
            'optionIds' => $optionIds,
        ];

        return $this->groupCache[$code];
    }

    private function getProductConfigurableOptionIds(ProductModel $product): array
    {
        $optionIds = [];

        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $configurableAttributes = $typeInstance->getConfigurableAttributes($product);

        foreach ($configurableAttributes as $attribute) {
            $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
            $optionIds[$attributeCode] = [];

            $options = $attribute->getOptions();
            if (is_array($options)) {
                foreach ($options as $option) {
                    if (isset($option['value_index'])) {
                        $optionIds[$attributeCode][] = (int)$option['value_index'];
                    }
                }
            }
        }

        return $optionIds;
    }

    private function findFirstMatchingOptionId(array $groupOptionIds, array $productOptionIds): ?string
    {
        $productOptionIdsInt = array_map('intval', $productOptionIds);

        foreach ($groupOptionIds as $groupOptionId) {
            $groupOptionIdInt = (int)$groupOptionId;
            if (in_array($groupOptionIdInt, $productOptionIdsInt, true)) {
                return (string)$groupOptionId;
            }
        }

        return null;
    }
}
