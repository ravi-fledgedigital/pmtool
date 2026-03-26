<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\ImportFactory;
use Vaimo\OTScene7AsicsIntegration\Model\Import\ArraySourceFactory;

class UpdateAvailableAngles
{
    private ImportFactory $importFactory;
    private ArraySourceFactory $arraySourceFactory;

    public function __construct(
        ImportFactory $importFactory,
        ArraySourceFactory $arraySourceFactory
    ) {
        $this->importFactory = $importFactory;
        $this->arraySourceFactory = $arraySourceFactory;
    }

    /**
     * @param string[][] $data
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $source = $this->arraySourceFactory->create(['data' => $data]);
        $import = $this->importFactory->create();
        $import->setData([
            'entity' => 'catalog_product',
            'behavior' => 'append',
            'validation_strategy' => 'validation-skip-errors',
        ]);
        $validation = $import->validateSource($source);

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ($validation && $import->getErrorAggregator()->getInvalidRowsCount() < \count($data)) {
            $import->importSource();
        }
    }
}
