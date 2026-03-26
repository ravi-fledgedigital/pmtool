<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\ApplicationServer\GraphQl\SchemaGenerator;
use Magento\Eav\Model\Config;
use Magento\Framework\GraphQl\Config\Data;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\Config as SearchRequestConfig;

class ReloadProcessor implements \Magento\Framework\App\State\ReloadProcessorInterface
{
    /**
     * @param SearchRequestConfig $searchRequestConfig
     * @param SchemaGenerator $schemaGenerator
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly SearchRequestConfig $searchRequestConfig,
        private readonly SchemaGenerator $schemaGenerator,
        private readonly Config $config,
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * Tells the system state to reload itself.
     *
     * @return void
     */
    public function reloadState(): void
    {
        // phpstan:ignore "Class Magento\Framework\GraphQl\Config\Data not found."
        $this->objectManager->get(Data::class)->reinitData();
        $this->searchRequestConfig->reinitData();
        $this->schemaGenerator->reset();
        $this->config->clearWithoutCleaningCache();
    }
}
