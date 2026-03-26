<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPFileExport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class ConfigProvider implements ExportConfigProviderInterface, FtpConfigProviderInterface
{
    private ?string $folderConfigPath;
    private ?string $filenameConfigPath;
    private ?string $schedulerEnabledConfigPath;

    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        ?string $folderConfigPath = null,
        ?string $filenameConfigPath = null,
        ?string $schedulerEnabledConfigPath = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->folderConfigPath = $folderConfigPath;
        $this->filenameConfigPath = $filenameConfigPath;
        $this->schedulerEnabledConfigPath = $schedulerEnabledConfigPath;
    }

    public function getFolderPath(): string
    {
        return $this->scopeConfig->getValue($this->folderConfigPath);
    }

    public function getFilename(): string
    {
        return $this->scopeConfig->getValue($this->filenameConfigPath);
    }

    public function isSchedulerEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag($this->schedulerEnabledConfigPath);
    }

    public function getServerHost(): string
    {
        return $this->scopeConfig->getValue(self::HOST_CONFIG_PATH);
    }

    public function getPort(): int
    {
        return (int) $this->scopeConfig->getValue(self::PORT_CONFIG_PATH);
    }

    public function getUsername(): string
    {
        return $this->scopeConfig->getValue(self::USERNAME_CONFIG_PATH);
    }

    public function getPrivateKeyContent(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::PRIVATE_KEY_CONFIG_PATH));
    }
}
