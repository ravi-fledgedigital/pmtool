<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Vaimo\AEPFileExport\Model;

interface FileUploaderInterface
{
    public function uploadFile(string $sourceFileName, string $fileName, ?string $folder = null): void;

    public function uploadData(string $data, string $fileName, ?string $folder = null): void;
}
