<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportCsv\Model;

use Magento\ImportCsvApi\Api\Data\LocalizedSourceDataInterface;
use Magento\ImportCsvApi\Api\ImportLocalizedSourceDataInterface;
use Magento\ImportCsvApi\Api\StartImportInterface;
use Magento\ImportExport\Model\LocaleEmulatorInterface;

/**
 * @inheritdoc
 */
class ImportLocalizedSourceData implements ImportLocalizedSourceDataInterface
{
    /**
     * @param StartImportInterface $startImport
     * @param LocaleEmulatorInterface $localeEmulator
     */
    public function __construct(
        private readonly StartImportInterface $startImport,
        private readonly LocaleEmulatorInterface $localeEmulator
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(
        LocalizedSourceDataInterface $source
    ): array {
        return $this->localeEmulator->emulate(
            fn () => $this->startImport->execute($source),
            $source->getLocale() ?: null
        );
    }
}
