<?php

namespace Firebear\PlatformNetsuite\Model\Export\Adapter;

use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Export\Adapter\Csv as AbstractAdapter;

class Attribute extends AbstractAdapter
{
    /**
     * Adapter Data
     *
     * @var []
     */
    protected $_data;

    /**
     * @var \Firebear\ImportExport\Model\Export\Adapter\Gateway\Attribute
     */
    protected $gateway;

    /**
     * Order constructor.
     * @param Filesystem $filesystem
     * @param Gateway\Attribute $gateway
     * @param null $destination
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Attribute $gateway,
        $destination = null,
        array $data = []
    ) {
        $this->gateway = $gateway;
        $this->_data = $data;
        if (isset($data['behavior_data'])) {
            $data = $data['behavior_data'];
            $this->gateway->setBehaviorData($data);
            $this->_delimiter = $data['separator'] ?? $this->_delimiter;
            $this->_enclosure = $data['enclosure'] ?? $this->_enclosure;
        }

        parent::__construct(
            $filesystem,
            $destination
        );
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws \Exception
     * @return $this
     */
    public function writeRow(array $rowData)
    {
        $rowData['entity'] = $this->_data['entity'];
        $this->gateway->exportAttribute($rowData);

        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }
        if (null === $this->_headerCols) {
            $this->_headerCols = [];
        }
        $this->_fileHandler->writeCsv(
            array_merge($this->_headerCols, array_intersect_key($rowData, $this->_headerCols)),
            $this->_delimiter,
            $this->_enclosure
        );
        return $this;
    }
}
