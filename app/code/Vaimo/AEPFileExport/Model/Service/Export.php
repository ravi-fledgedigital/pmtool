<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPFileExport\Model\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Psr\Log\LoggerInterface;
use Vaimo\AEPFileExport\Model\ExportConfigProviderInterface;
use Vaimo\AEPFileExport\Model\ExportEntityInterface;
use Vaimo\AEPFileExport\Model\FileUploaderInterface;

class Export
{
    private CsvFactory $csvFactory;
    private ExportEntityInterface $export;
    private FileUploaderInterface $fileUploader;
    private ExportConfigProviderInterface $configProvider;
    private DateTime $dateTime;
    private LoggerInterface $logger;
    private string $writerDestination;

    public function __construct(
        CsvFactory $csvFactory,
        ExportEntityInterface $export,
        FileUploaderInterface $fileUploader,
        ExportConfigProviderInterface $configProvider,
        DateTime $dateTime,
        LoggerInterface $logger,
        string $writerDestination
    ) {
        $this->csvFactory = $csvFactory;
        $this->export = $export;
        $this->fileUploader = $fileUploader;
        $this->configProvider = $configProvider;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->writerDestination = $writerDestination;
    }

    public function execute()
    {
        try {
            $writer = $this->csvFactory->create(['destination' => $this->writerDestination]);
            $this->export->setWriter($writer);

            $filename = $this->configProvider->getFilename()
                . $this->dateTime->date('ymdHis')
                . '.csv';

            $this->export->export();
            if ($this->export->getProcessedRowsCount() === 0) {
                return;
            }

            $this->fileUploader->uploadFile(
                DirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . $this->writerDestination,
                $filename,
                $this->configProvider->getFolderPath()
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }
}
