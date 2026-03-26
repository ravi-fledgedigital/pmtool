<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpExport;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use OnitsukaTiger\Logger\Api\Logger;
use XMLWriter;
use Laminas\Config\Writer\Xml;

/**
 * Class ExportXml
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpExport
 */
class ExportXml extends Xml {

    const PREFIX_SHIPMENT = 3;
    const PREFIX_RETURN = 8;
    const PREFIX_ORDER = 1;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * ExportXml constructor.
     * @param TimezoneInterface $localeDate
     * @param Logger $logger
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Logger $logger
    ){
        $this->localeDate = $localeDate;
        $this->logger = $logger;
    }

    /**
     * @param array $config
     * @return string
     */
    public function processConfig(array $config): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('root');

        foreach ($config as $sectionName => $data) {
            if (!is_array($data)) {
                $writer->writeElement($sectionName, (string) $data);
            } else {
                $this->addBranch($sectionName, $data, $writer);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * @param array $data
     * @param $fileName
     * @return false|int
     */
    public function exportToFileXml (array $data, $fileName) {
        $content = $this->processConfig($data);
        $this->logger->debug($content);
        return file_put_contents($fileName, $content);
    }

    /**
     * @param $number
     * @param $prefix
     * @return string
     */
    public function addPrefix($number, $prefix): string
    {
        return $prefix.sprintf("%'.010d", $number);
    }

    /**
     * @param string $format
     * @param $storeId
     * @return mixed
     */
    public function getTimeZoneDatetimeString(string $format, $storeId) {
        return $this->localeDate->scopeDate($storeId,null,true)->format($format);
    }
}
