<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor;

use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList2;
use Magento\Framework\Data\Collection\FilesystemFactory as FilesystemCollectionFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileSystemFile;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Combine the StateMonitor outputs from each request into a single request.
 */
class AggregateOutput
{
    /**
     * @param DirectoryList $directoryList
     * @param FilesystemCollectionFactory $filesystemCollectionFactory
     * @param FileSystemFile $fileSystemFile
     * @param JsonSerializer $jsonSerializer
     * @param AggregateOutput\OutputInterface[] $outputs
     */
    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly FilesystemCollectionFactory $filesystemCollectionFactory,
        private readonly FileSystemFile $fileSystemFile,
        private readonly JsonSerializer $jsonSerializer,
        private readonly array $outputs = [],
    ) {
    }

    /**
     * Aggregates the output files and returns string containing the aggregate files
     *
     * @return string
     */
    public function execute() : string
    {
        $tempDirectory = $this->directoryList->getPath(DirectoryList2::TMP);
        $filesystemCollection = $this->filesystemCollectionFactory->create();
        $filesystemCollection->addTargetDir($tempDirectory);
        $filesystemCollection->setCollectRecursively(false);
        $filesystemCollection->setFilesFilter('/^StateMonitor\-thread\-output-\d+\-/');
        $failedClasses = [];
        $aggregatedFiles = [];
        $failedRequests = [];
        foreach ($filesystemCollection as $file) {
            $filename = $file->getFilename();
            $aggregatedFiles[] = $filename;
            $singleOutput = $this->jsonSerializer->unserialize($this->fileSystemFile->fileGetContents($filename));
            $requestName = $singleOutput['requestName'];
            if ($requestName) {
                $failedRequests[$requestName][] = $singleOutput['failedClasses'];
                foreach ($singleOutput['failedClasses'] as &$failedClass) {
                    $failedClass['requestName'][$requestName] = true;
                }
            }
            $failedClasses[] = $singleOutput['failedClasses'];
        }
        if (empty($failedClasses)) {
            return "No output files to aggregate!\n";
        }
        $errorsByClassName = array_replace_recursive(...$failedClasses);
        ksort($errorsByClassName);
        $errorsByRequestName = [];
        foreach ($failedRequests as $requestName => $unmergedFailedClasses) {
            $errorsByRequestName[$requestName] = array_replace_recursive(...$unmergedFailedClasses);
            ksort($errorsByRequestName[$requestName]);
        }
        ksort($errorsByRequestName);
        $returnValue = '';
        foreach ($this->outputs as $output) {
            $filename = $output->doOutput($errorsByClassName, $errorsByRequestName);
            $returnValue .= sprintf("%s\n", $filename);
        }
        foreach ($aggregatedFiles as $aggregatedFile) {
            $this->fileSystemFile->deleteFile($aggregatedFile);
        }
        return $returnValue;
    }
}
