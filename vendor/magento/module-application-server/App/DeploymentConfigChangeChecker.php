<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileSystemFile;

/**
 * Checks for changes in deployment config files.
 *
 * Compares files by hashing their contents.
 * If file a deployment config file has changed, and was already checked before, invalidate its opcache.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeploymentConfigChangeChecker
{
    /**
     * @var int[]
     */
    private array $fileHashes;

    /**
     * @param ConfigFilePool $configFilePool
     * @param DirectoryList $directoryList
     * @param FileSystemFile $fileSystemFile
     */
    public function __construct(
        private readonly ConfigFilePool $configFilePool,
        private readonly DirectoryList $directoryList,
        private readonly FileSystemFile $fileSystemFile,
    ) {
        $this->fileHashes = $this->getFileHashesAndInvalidateOpCache();
    }

    /**
     * Checks the files' hashes for later comparison. Also invalidates Op Cache if previous hash existed.
     *
     * @param array $previousHashes
     * @return int[]
     */
    private function getFileHashesAndInvalidateOpCache(array $previousHashes = []) : array
    {
        $basePath = $this->directoryList->getPath(DirectoryList::CONFIG);
        $fileHashes = [];
        foreach ($this->configFilePool->getPaths() as $filePath) {
            try {
                $fileHashes[$filePath] = hash(
                    'xxh3',
                    $this->fileSystemFile->fileGetContents($basePath . '/' . $filePath)
                );
                if (($previousHashes[$filePath] ?? null) && $previousHashes[$filePath] != $fileHashes[$filePath]) {
                    if (function_exists('opcache_invalidate')) {
                        opcache_invalidate($basePath . '/' . $filePath, true);
                    }
                }
            } catch (FileSystemException $exception) {
                $fileHashes[$filePath] = '';
            }
        }
        return $fileHashes;
    }

    /**
     * Compares the files' hashes to see if they've changed.
     *
     * @return bool
     */
    public function haveFilesChanged() : bool
    {
        $currentFileHashes = $this->getFileHashesAndInvalidateOpCache($this->fileHashes);
        if ($currentFileHashes == $this->fileHashes) {
            return false;
        }
        $this->fileHashes = $currentFileHashes;
        return true;
    }
}
