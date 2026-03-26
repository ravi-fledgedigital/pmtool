<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\BundleStaging\Model\Product;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Bundle\Model\Product\SaveHandler as BundleSaveHandler;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Bundle product save handler at Staging
 */
class SaveHandler implements ExtensionInterface
{

    /**
     * @param BundleSaveHandler $saveHandler
     */
    public function __construct(private readonly BundleSaveHandler $saveHandler)
    {
    }

    /**
     * Clean cache on Bundle product attribute at staging
     *
     * @param object $entity
     * @param array $arguments
     *
     * @return ProductInterface|object
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getTypeId() === Type::TYPE_BUNDLE &&
            $entity->hasData('_cache_instance_options_collection')
        ) {
            $entity->unsetData('_cache_instance_options_collection');
        }
        return $this->saveHandler->execute($entity, $arguments);
    }
}
