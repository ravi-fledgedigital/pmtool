<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPFileExport\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use phpseclib3\Crypt\RSA;
use phpseclib3\Net\SFTP;
use Vaimo\AEPFileExport\Exception\AEPFileExportException;
use OnitsukaTiger\Logger\Kerry\Logger;

class FileUpload implements FileUploaderInterface
{
    private FtpConfigProviderInterface $configProvider;
    private DriverInterface $fileDriver;
    private Logger $logger;

    public function __construct(
        FtpConfigProviderInterface $configProvider,
        DriverInterface $fileDriver,
        Logger $logger
    ) {
        $this->configProvider = $configProvider;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    /**
     * @param string $sourceFileName
     * @param string $fileName
     * @param string|null $folder
     * @return void
     * @throws AEPFileExportException
     * @throws FileSystemException
     */
    public function uploadFile(
        string $sourceFileName,
        string $fileName,
        ?string $folder = null
    ): void {
        \set_error_handler([$this, 'errorHandler'], E_USER_NOTICE);
        $client = $this->getClient();
        $this->checkFolder($folder, $client);


        if (!$this->fileDriver->isFile($sourceFileName)) {
            throw new AEPFileExportException(\__("File %1 doesn't exists", $sourceFileName));
        }

        //phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if (!$client->put($folder."/".$fileName, $sourceFileName, SFTP::SOURCE_LOCAL_FILE)) {
            $this->processUploadingErrors($client);
        }
    }

    /**
     * @param string $data
     * @param string $fileName
     * @param string|null $folder
     * @return void
     * @throws AEPFileExportException
     */
    public function uploadData(
        string $data,
        string $fileName,
        ?string $folder = null
    ): void {
        \set_error_handler([$this, 'errorHandler'], E_USER_NOTICE);
        $client = $this->getClient();
        $this->checkFolder($folder, $client);
        //phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if (!$client->put($fileName, $data, SFTP::SOURCE_STRING)) {
            $this->processUploadingErrors($client);
        }
    }

    /**
     * @param int $errorNumber
     * @param string $errorString
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws AEPFileExportException
     */
    public function errorHandler(
        int $errorNumber, //phpcs:ignore Vaimo.CodeAnalysis.UnusedFunctionParameter.Found
        string $errorString,
        string $errorFile,
        int $errorLine //phpcs:ignore Vaimo.CodeAnalysis.UnusedFunctionParameter.Found
    ): bool {
        if (\strpos($errorFile, 'phpseclib') === false) {
            return false;
        }

        throw new AEPFileExportException(__($errorString));
    }

    /**
     * @return SFTP
     * @throws AEPFileExportException
     */
    private function getClient(): SFTP
    {
        /** @var SFTP $client */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $client = new SFTP($this->configProvider->getServerHost(), $this->configProvider->getPort());//$objectManager->create(SFTP::class);
        //echo $this->configProvider->getServerHost();exit;
        //$client = $sftpFactory->create($this->configProvider->getServerHost(), $this->configProvider->getPort());

        //$rsaFactory = $objectManager->create(RSA::class);

        /** @var RSA $key */
        //$key = $rsaFactory;
        $key = RSA::load($this->configProvider->getPrivateKeyContent());
        if (!$client->login($this->configProvider->getUsername(), $key)) {
            throw new AEPFileExportException(\__('Cannot login to SFTP server'));
        }

        return $client;
    }

    private function checkFolder(?string $folder, SFTP $client): void
    {
        if ($folder === null) {
            return;
        }
        $folders = explode('/', $folder);
        foreach($folders as $folderssub) {
            if (!$client->is_dir($folderssub)) {
                $client->mkdir($folderssub);
            }

            $client->chdir($folderssub);
        }
    }

    /**
     * @param SFTP $client
     * @return void
     * @throws AEPFileExportException
     */
    private function processUploadingErrors(SFTP $client): void
    {
        $message = '';
        $errors = $client->getSFTPErrors();

        foreach ($errors as $error) {
            $message .= $error . "\n";
        }

        throw new AEPFileExportException(\__("Cannot upload file to server: \n" . $message));
    }
}
