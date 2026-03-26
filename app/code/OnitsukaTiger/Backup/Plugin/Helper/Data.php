<?php
namespace OnitsukaTiger\Backup\Plugin\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Data
{
    const DIRECTORY_PRODUCT_IMAGE_CACHE = 'catalog/product/cache';

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * Data constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->_filesystem = $filesystem;
    }

    /**
     * @param \Magento\Backup\Helper\Data $subject
     * @param $result
     * @return mixed
     */
    public function afterGetBackupIgnorePaths(
        \Magento\Backup\Helper\Data $subject,
        $result
    ) {
        $result[] = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(self::DIRECTORY_PRODUCT_IMAGE_CACHE);
        return $result;
    }
}
