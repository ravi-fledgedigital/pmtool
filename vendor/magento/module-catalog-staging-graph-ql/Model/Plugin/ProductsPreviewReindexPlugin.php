<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Plugin;

use Magento\CatalogGraphQl\Model\Resolver\Products;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Staging\Model\VersionManager;
use Magento\CatalogStaging\Model\Indexer\Category\Product\PreviewReindex;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ProductsPreviewReindexPlugin
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var PreviewReindex
     */
    private $previewReindex;

    /**
     * @param VersionManager $versionManager
     * @param PreviewReindex $previewReindex
     */
    public function __construct(
        VersionManager $versionManager,
        PreviewReindex $previewReindex
    ) {
        $this->versionManager = $versionManager;
        $this->previewReindex = $previewReindex;
    }

    /**
     * Reindex categories/products relations for preview queries.
     *
     * @param Products $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        Products $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): void {
        if ($this->versionManager->isPreviewVersion()) {
            if (!isset($args['filter']['category_id']['in'])) {
                $store = $context->getExtensionAttributes()->getStore();
                $this->previewReindex->reindex((int)$store->getRootCategoryId(), (int)$store->getId());
            }
        }
    }
}
