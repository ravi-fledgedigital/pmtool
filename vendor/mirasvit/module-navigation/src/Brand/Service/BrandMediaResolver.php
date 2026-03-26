<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;

class BrandMediaResolver
{
    public const BASE_PATH = 'brand/brand';
    public const BASE_TMP_PATH = 'brand/tmp/brand';

    private ReadInterface $mediaDirectory;

    public function __construct(Filesystem $filesystem)
    {
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    public function resolveFilePath(string $fileName): ?string
    {
        $basePath = self::BASE_PATH . '/' . $fileName;
        if ($this->mediaDirectory->isExist($basePath)) {
            return $basePath;
        }

        $tmpPath = self::BASE_TMP_PATH . '/' . $fileName;
        if ($this->mediaDirectory->isExist($tmpPath)) {
            return $tmpPath;
        }

        return null;
    }

    public function resolveBasePath(string $fileName): string
    {
        if ($this->mediaDirectory->isExist(self::BASE_PATH . '/' . $fileName)) {
            return self::BASE_PATH;
        }

        if ($this->mediaDirectory->isExist(self::BASE_TMP_PATH . '/' . $fileName)) {
            return self::BASE_TMP_PATH;
        }

        return self::BASE_PATH;
    }

    public function getMediaDirectory(): ReadInterface
    {
        return $this->mediaDirectory;
    }
}
