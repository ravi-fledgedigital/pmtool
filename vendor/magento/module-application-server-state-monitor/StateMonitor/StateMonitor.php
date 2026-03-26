<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServerStateMonitor\StateMonitor;

use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList2;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileSystemFile;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\TestFramework\ApplicationStateComparator\Comparator;

/**
 * StateMonitor monitors state of ApplicationServer by using Comparator
 */
class StateMonitor
{

    /**
     * @param Comparator $comparator
     * @param DirectoryList $directoryList
     * @param FileSystemFile $fileSystemFile
     * @param JsonSerializer $jsonSerializer
     * @param RequestNameInterface[] $requestNames
     */
    public function __construct(
        private readonly Comparator $comparator,
        private readonly DirectoryList $directoryList,
        private readonly FileSystemFile $fileSystemFile,
        private readonly JsonSerializer $jsonSerializer,
        private readonly array $requestNames = [],
    ) {
    }

    /**
     * Uses Comparator to find objects that change state.
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function execute() : void
    {
        $result = $this->comparator->compareConstructedAgainstCurrent('*');
        if ($result) {
            $requestName = '';
            foreach ($this->requestNames as $requstNameGetter) {
                $requestName = $requstNameGetter->getRequestName();
                if ($requestName) {
                    break;
                }
            }
            $tempDirectory = $this->directoryList->getPath(DirectoryList2::TMP);
            // Note: Magento doesn't currently have tempnam in its abstractions.
            $tempFileName = tempnam($tempDirectory, 'StateMonitor-thread-output-' . getmypid() . '-');
            $output = [
                'failedClasses' => $result,
                'requestName' => $requestName,
            ];
            $this->fileSystemFile->filePutContents($tempFileName, $this->jsonSerializer->serialize($output), 0);
        }
    }
}
