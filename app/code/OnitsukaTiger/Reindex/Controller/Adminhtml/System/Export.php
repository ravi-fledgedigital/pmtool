<?php

namespace OnitsukaTiger\Reindex\Controller\Adminhtml\System;

use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Config\Controller\Adminhtml\System\AbstractConfig;
use OnitsukaTiger\Reindex\Helper\Data;

/**
 * Class Export
 * @package OnitsukaTiger\Reindex\Controller\Adminhtml\System
 */
class Export extends AbstractConfig
{
    const DIR = 'app';
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * Export constructor.
     * @param Context $context
     * @param Structure $configStructure
     * @param ConfigSectionChecker $sectionChecker
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Export shipping table rates in csv format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = Data::FILENAME;
        $content['type'] = 'filename';
        $content['value'] = Data::FILEPATH;
        return $this->fileFactory->create($fileName, $content, self::DIR);
    }
}
