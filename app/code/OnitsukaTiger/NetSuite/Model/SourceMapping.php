<?php


namespace OnitsukaTiger\NetSuite\Model;

/**
 * Class SourceMapping
 * @package OnitsukaTiger\NetSuite\Model
 */
class SourceMapping
{
    const SOURCE_MAPPING = 'netsuite/general/sources';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var array
     */
    protected $mappingFromNetSuite;
    /**
     * @var array
     */
    protected $mappingFromMagento;

    /**
     * NetSuite constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $mappingFromNetSuite = [];
        $mappingFromMagento = [];
        foreach (json_decode($this->scopeConfig->getValue(self::SOURCE_MAPPING)) as $row) {
            $mappingFromNetSuite[$row->netsuite_id] = $row->source;
            $mappingFromMagento[$row->source] = $row->netsuite_id;
        }

        $this->mappingFromNetSuite = $mappingFromNetSuite;
        $this->mappingFromMagento = $mappingFromMagento;
    }

    /**
     * Get Magento location code from NetSuite location code
     * @param $code
     * @return mixed|null
     */
    public function getMagentoLocation($code)
    {
        $ret = null;
        if (array_key_exists($code, $this->mappingFromNetSuite)) {
            $ret = $this->mappingFromNetSuite[$code];
        }
        return $ret;
    }

    /**
     * Get NetSuite location code from Magento location code
     * @param $code
     * @return mixed|null
     */
    public function getNetSuiteLocation($code)
    {
        $ret = null;
        if (array_key_exists($code, $this->mappingFromMagento)) {
            $ret = $this->mappingFromMagento[$code];
        }
        return $ret;
    }
}
