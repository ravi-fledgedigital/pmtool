<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor\AggregateOutput;

use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList2;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileSystemFile;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * JSON output for AggregateOutput of StateMonitor
 */
class Json implements OutputInterface
{
    /**
     * @param DirectoryList $directoryList
     * @param FileSystemFile $fileSystemFile
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly FileSystemFile $fileSystemFile,
        private readonly JsonSerializer $jsonSerializer,
    ) {
    }

    /**
     * @inheritDoc
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function doOutput(array $errorsByClassName, array $errorsByRequestName) : string
    {
        $timestamp = gmdate('Y-m-d\\TH:i:sp');
        $tempDirectory = $this->directoryList->getPath(DirectoryList2::TMP);
        // Note: Magento doesn't currently have tempnam in its abstractions.
        $tempFileNameToCleanup = tempnam($tempDirectory, 'StateMonitor-json-' . $timestamp . '-');
        $tempFileName = $tempFileNameToCleanup . '.json';
        $output = $this->jsonSerializer->serialize([
            'errorsByClassName' => $errorsByClassName,
            'errorsByRequestName' => $errorsByRequestName,
        ]);
        $this->fileSystemFile->filePutContents($tempFileName, $output, 0);
        $this->fileSystemFile->deleteFile($tempFileNameToCleanup);
        return $tempFileName;
    }
}
