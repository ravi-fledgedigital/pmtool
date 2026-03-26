<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPFileExport\Model;

interface FtpConfigProviderInterface
{
    public const HOST_CONFIG_PATH = 'aep_sftp/access/host';
    public const PORT_CONFIG_PATH = 'aep_sftp/access/port';
    public const USERNAME_CONFIG_PATH = 'aep_sftp/access/username';
    public const PRIVATE_KEY_CONFIG_PATH = 'aep_sftp/access/private_key';

    public function getServerHost(): string;

    public function getPort(): int;

    public function getUsername(): string;

    public function getPrivateKeyContent(): string;
}
