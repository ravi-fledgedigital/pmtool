<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace OnitsukaTigerKorea\SftpImportExport\Model\Import\Source;

use Firebear\ImportExport\Exception\XmlException as FirebearXmlException;
use Firebear\ImportExport\Model\Source\Platform\PlatformInterface;
use Firebear\ImportExport\Traits\Import\Map as ImportMap;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\Read as Directory;
use Magento\Framework\Xml\Parser;
use SimpleXMLIterator;

/**
 * XML Import Adapter
 */
class Xml extends \Firebear\ImportExport\Model\Import\Source\Xml
{
    use ImportMap;

    const CREATE_ATTRIBUTE = 'create_attribute';

    /**
     * @var SimpleXMLIterator
     */
    protected $reader;

    private $lastRead;

    private $elementStack;

    protected $maps;

    protected $extension = 'xml';

    protected $mimeTypes = [
        'text/xml',
        'text/plain',
        'application/excel',
        'application/xml',
        'application/vnd.ms-excel',
        'application/vnd.msexcel'
    ];

    /**
     * Platform
     *
     * @var \Firebear\ImportExport\Model\Source\Platform\PlatformInterface
     */
    protected $platform;

    /**
     * Iterator Lock Flag
     *
     * @var bool
     */
    protected $_lock = false;

    /**
     * Prepared Items
     *
     * @var array
     */
    protected $_items = [];

    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = [];

    /**
     * @var Parser
     */
    protected $parser;


    /**
     * Initialize Adapter
     *
     * @param array $file
     * @param Directory $directory
     * @param PlatformInterface $platform
     * @param array $data
     * @param Parser $parser
     * @throws LocalizedException
     * @throws FirebearXmlException
     */
    public function __construct(
        $file,
        Directory $directory,
        Parser $parser,
        PlatformInterface $platform = null,
        $data = []
    ) {
        $this->parser = $parser;
        $this->_data = $data;
        $filePath = $file;
        if (0 !== strpos($file, $directory->getAbsolutePath())) {
            $filePath =  $directory->getAbsolutePath($file);
        }

        $result = $this->checkMimeType($filePath);

        if ($result !== true) {
            throw new LocalizedException($result);
        }

        libxml_use_internal_errors(true);

        $this->platform = $platform;
        $result = $this->parser->load($filePath)->xmlToArray();
        foreach ($result['root'] as $items) {
            if (!empty($items[0]['stock']) && $data['entity'] == 'stock_sources_qty') {
                $this->sortStock($filePath);
            }
        }
        $this->reader = simplexml_load_file(
            $filePath,
            SimpleXMLIterator::class
        );

        if (false === $this->reader) {
            throw new FirebearXmlException(libxml_get_errors());
        }

        $this->reader->rewind();
        $this->getColumns();
        parent::__construct($file,$directory,$platform,$data);
    }

    public function sortStock($file)
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->load($file);

        $root = $doc->getElementsByTagName('root');
        $root = $root[0];
        $rows = [];
        $nodes = $root->getElementsByTagName('Item');
        while ($row = $nodes->item(0)) {
            $rows []= $root->removeChild($row);
        }
        usort($rows, function ($a, $b) {
            $a_name = $a->getElementsByTagName('stock');
            $b_name = $b->getElementsByTagName('stock');

            return ($a_name->length && $b_name->length) ?
                strcmp(trim($a_name[0]->textContent), trim($b_name[0]->textContent)) : 0;
        });
        foreach ($rows as $row) {
            $root->appendChild($row);
        }

        $doc->saveXML();
        $doc->save($file);
    }
}
