<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @phpstan-type ProductTypeMapping array{
 *    product_type: string,
 *    asset_angle: string,
 *    priority: string
 * }
 * @phpstan-type AnglesMapping array{
 *    product_type: string,
 *    role: string,
 *    angle: string
 * }
 */
class ConfigProvider
{
    public const ENABLED_PATH = 'catalog/scene7/enabled';
    public const ANGLES_ROLE_MAPPING = 'catalog/scene7/angles_role_mapping';
    public const PRODUCT_TYPE_MAPPING = 'catalog/scene7/product_type_mapping';
    public const RESAMPLING_MODE = 'catalog/scene7/resampling_mode';

    private ScopeConfigInterface $scopeConfig;
    private SerializerInterface $serializer;

    public function __construct(ScopeConfigInterface $scopeConfig, SerializerInterface $serializer)
    {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::ENABLED_PATH);
    }

    /**
     * @return AnglesMapping[]
     */
    public function getAnglesMapping(?string $productGroup = null): array
    {
        $value = $this->scopeConfig->getValue(self::ANGLES_ROLE_MAPPING);
        try {
            $result = $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $e) {
            // json structure is broken, or either no value.
            return [];
        }

        if ($productGroup === null) {
            return $result;
        }

        foreach ($result as $index => $item) {
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($item['product_type'] !== $productGroup) {
                unset($result[$index]);
            }
        }

        return $result;
    }

    /**
     * @param string|null $productGroup
     * @return ProductTypeMapping[]
     */
    public function getProductTypesMapping(?string $productGroup = null): array
    {
        $value = $this->scopeConfig->getValue(self::PRODUCT_TYPE_MAPPING);
        try {
            $result = $this->serializer->unserialize($value);
        } catch (\InvalidArgumentException $e) {
            // json structure is broken, or either no value.
            return [];
        }

        if ($productGroup === null) {
            return $this->sortTypesMappingByPriority($result);
        }

        foreach ($result as $index => $item) {
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
            if ($item['product_type'] !== $productGroup) {
                unset($result[$index]);
            }
        }

        return $this->sortTypesMappingByPriority($result);
    }

    /**
     * @param ProductTypeMapping[] $mapping
     * @return ProductTypeMapping[]
     */
    private function sortTypesMappingByPriority(array $mapping): array
    {
        usort($mapping, static function ($left, $right) {
            $leftPriority = $left['priority'] === '' ? 9999999 : (int) $left['priority'];
            $rightPriority = $right['priority'] === '' ? 9999999 : (int) $right['priority'];

            if ($leftPriority == $rightPriority) {
                return 0;
            }

            // phpcs:ignore Vaimo.ControlStructures.TernaryOperator.OperatorInThen
            return $leftPriority < $rightPriority ? -1 : 1;
        });

        return $mapping;
    }

    public function getResamplingMode(): ?string
    {
        return $this->scopeConfig->getValue(self::RESAMPLING_MODE);
    }
}
