<?php

declare(strict_types=1);

namespace Vaimo\AEPFileExportPlugin\Plugin\Model;

use Vaimo\AEPFileExport\Model\ConfigProvider as Subject;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Plugin for update private key
 */
class ConfigProvider
{
    /**
     * @var DirectoryList
     */
    private DirectoryList $directory;

    /**
     * @var File
     */
    private File $fileDriver;

    /**
     * @var DriverInterface
     */
    private DriverInterface $driverInterface;

    /**
     * Constructor
     *
     * @param DirectoryList $directory
     * @param File $fileDriver
     * @param DriverInterface $driverInterface
     */
    public function __construct(
        DirectoryList $directory,
        File $fileDriver,
        DriverInterface $driverInterface
    ) {
        $this->directory = $directory;
        $this->fileDriver = $fileDriver;
        $this->driverInterface = $driverInterface;
    }

    /**
     * Plugin for private key
     *
     * @param Subject $subject
     * @param string $result
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetPrivateKeyContent(Subject $subject, string $result): string
    {
        $privateKey = $result;
        $privateKeyPath  =  $this->directory->getPath('var')."/aep_export/id_rsa";
        if ($this->fileDriver->isExists($privateKeyPath)) {
            $privateKey =  $this->driverInterface->fileGetContents($privateKeyPath);
        }

        return $privateKey;
    }
}
