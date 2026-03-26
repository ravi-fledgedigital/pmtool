<?php

namespace Cpss\Pos\Helper;

class Recovery
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteFactory
     */
    protected $writeFactory;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    public function __construct(
        \Magento\Framework\Filesystem\Directory\WriteFactory $writeFactory,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->writeFactory = $writeFactory;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    public function getWriteFactory() {
        return $this->writeFactory;
    }

    public function getDirectoryList() {
        return $this->directoryList;
    }

    public function getFile() {
        return $this->file;
    }
}