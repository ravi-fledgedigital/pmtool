<?php
declare(strict_types=1);
namespace OnitsukaTiger\Store\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const MONTH_TRANSLATE = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    ];

    const PATH_DATE_TRANSLATE = 'date_time/general/translate_date';
    const PATH_MAPPING_STORE = 'inventory_store/general/store_mapping';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\InventoryApi\Api\SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->mathRandom      = $mathRandom;
        $this->serializer      = $serializer;
        parent::__construct($context);
    }
    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }


    public function formatDate($date, $storeId)
    {
        $fomatString  = $this->getDateFormatByStore($storeId);
        $dateLocale = $this->_localeDate->date(new \DateTime($date));
        $dateFormat  = $dateLocale->format($fomatString);
        if ($this->getConfigValue(self::PATH_DATE_TRANSLATE, $storeId)) {
            foreach (['/','-'] as $separator) {
                foreach (explode($separator, $dateFormat) as $val) {
                    if (in_array($val,self::MONTH_TRANSLATE)) {
                        return str_replace($val,__($val)->render() ,$dateFormat);
                    }
                }
            }
        }

        return $dateFormat;
    }

    /**
     * @param $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore($storeId)
    {
        return $this->_storeManager->getStore($storeId);
    }

    public function formatDateTime($dateTime, $storeId)
    {
        $fomatString  = $this->getDateFormatByStore($storeId) . ' g:i:s A';
        $dateLocale = $this->_localeDate->date(new \DateTime($dateTime));
        $newDate = $dateLocale->format($fomatString);
        if (strpos($newDate, 'AM')) {
            return str_replace('AM',__('AM')->render() ,$newDate);
        } else {
            return str_replace('PM', __('PM')->render() ,$newDate);
        }
        return $newDate;
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getDateFormatByStore($storeId)
    {
        return $this->scopeConfig->getValue('date_time/general/date_format', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function formatDateOfDob($storeId)
    {
        return $this->scopeConfig->getValue('date_time/general/dob_date_format', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $sourceCode
     * @return int|string
     * @throws InputException
     */
    public function getStoreIdFromSourceCode(string $sourceCode)
    {
        if (is_array($this->getMappingStoreFormSource()) && count($this->getMappingStoreFormSource()) > 0) {
            foreach ($this->getMappingStoreFormSource() as $storeId => $sources) {
                if (is_array($sources) && in_array($sourceCode, $sources)) {
                    return $storeId;
                }
            }
        }

        throw new InputException(
            __(sprintf('Source not found with source code [%s]', $sourceCode))
        );
    }

    /**
     * @param $string
     * @return array|mixed|string|string[]|null
     */
    public function removeSpecialCharacter($string) {
        if ($string !== null) {
            $string = preg_replace('/![\x{0E00}-\x{0E7F}]/u', '', $string);
            $string = preg_replace('/[\x{200b}]/u', '', $string);
        }
        return $string;
    }

    /**
     * @return array
     */
    public function getMappingStoreFormSource() {
        $value = $this->scopeConfig->getValue(self::PATH_MAPPING_STORE, ScopeInterface::SCOPE_STORE);
        return $value ? $this->unserializeValue($value) : [];
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    public function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row) || !array_key_exists('store', $row) || !array_key_exists('source', $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);

        return $value;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    protected function serializeValue($value)
    {
        if (is_array($value)) {
            $data = [];
            foreach ($value as $storeId => $sources) {
                if (!array_key_exists($storeId, $data)) {
                    $data[$storeId] = $sources;
                }
            }
            return $this->serializer->serialize($data);
        }

        return '';
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     */
    public function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * @param array $value
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $storeId => $sources) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = [
                'store' => $storeId,
                'source' => $sources,
            ];
        }

        return $result;
    }

    /**
     * @param array $value
     * @return array
     */
    public function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row) || !array_key_exists('store', $row) || !array_key_exists('source', $row)) {
                continue;
            }
            if (!empty($row['store']) && !empty($row['source'])) {
                $result[$row['store']] = $row['source'];
            }
        }

        return $result;
    }

    /**
     * @param $value
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }

        return $value;
    }

    /**
     * @param $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCode($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getCode();
    }
}
