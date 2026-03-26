<?php

namespace OnitsukaTiger\Reindex\Model\Config\Source;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\Value;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use OnitsukaTiger\Reindex\Helper\Data;

/**
 * Class Xml
 * @package OnitsukaTiger\Reindex\Model\Config\Source
 */
class Xml extends Value
{
    /**
     * Errors in import process
     * @var array
     */
    protected $_errors = [];

    /**
     * Count of imported words
     * @var int
     */
    protected $_importedWords = 0;

    /**
     * Array of words to be imported
     * @var array
     */
    protected $_importWords = [];

    /**
     * @var ReadFactory
     */
    protected $_readFactory;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    protected $driver;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ReadFactory $readFactory
     * @param Filesystem $filesystem
     * @param DriverInterface $driver
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ReadFactory $readFactory,
        Filesystem $filesystem,
        DriverInterface $driver,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_readFactory = $readFactory;
        $this->_filesystem = $filesystem;
        $this->driver     = $driver;
    }

    /**
     * @return \Magento\Framework\Model\AbstractModel|void
     */
    public function afterSave()
    {
        $this->uploadAndImport($this);
        return parent::afterSave();
    }

    public function uploadAndImport(DataObject $object)
    {
        $dir = $this->_filesystem->getDirectoryWrite(Data::DIR);
        $filName = $dir->getAbsolutePath().Data::FILEPATH;

        $importFieldData = $object->getFieldsetDataValue('import');
        /* check if xml name is set */
        $rest = substr($importFieldData['name'], -4, 4);
        if (empty($importFieldData['tmp_name']) && $rest != '.xml') {
            return $this;
        }

        $xmlFile = $importFieldData['tmp_name'];
        $content = $this->driver->fileGetContents($xmlFile, null, null);
        file_put_contents($filName, $content);

        return $this;
    }

}
