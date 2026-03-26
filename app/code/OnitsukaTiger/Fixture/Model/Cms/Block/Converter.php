<?php
namespace OnitsukaTiger\Fixture\Model\Cms\Block;

/**
 * Class Converter
 * @package OnitsukaTiger\Fixture\Model\Cms\Block
 */
class Converter
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Store manager.
     */
    protected $_storeManager;

    /**
     * @var array $_storesId Stored stores codes with ID.
     */
    protected $_storesId = [];

    /**
     * Converter constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * Convert CSV format row to array
     *
     * @param array $row
     * @return array
     */
    public function convertRow($row)
    {
        $data = [];
        foreach ($row as $field => $value) {
            if ('content' == $field) {
                $data['block'][$field] = $this->replaceMatches($value);
                continue;
            }
            $data['block'][$field] = $value;
        }
        return $data;
    }

    /**
     * Get formatted array value
     *
     * @param mixed $value
     * @param string $separator
     * @return array
     */
    protected function getArrayValue($value, $separator = "/")
    {
        if (is_array($value)) {
            return $value;
        }
        if (false !== strpos($value, $separator)) {
            $value = array_filter(explode($separator, $value));
        }
        return !is_array($value) ? [$value] : $value;
    }

    /**
     * @param string $content
     * @return mixed
     */
    protected function replaceMatches($content)
    {
        $matches = $this->getMatches($content);
        if (!empty($matches['value'])) {
            $replaces = $this->getReplaces($matches);
            $content = preg_replace($replaces['regexp'], $replaces['value'], $content);
        }
        return $content;
    }

    /**
     * @param string $content
     * @return array
     */
    protected function getMatches($content)
    {
        $regexp = '/{{(category[^ ]*) key="([^"]+)"}}/';
        preg_match_all($regexp, $content, $matchesCategory);
        $regexp = '/{{(product[^ ]*) sku="([^"]+)"}}/';
        preg_match_all($regexp, $content, $matchesProduct);
        $regexp = '/{{(attribute) key="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesAttribute);
        return [
            'type' => $matchesCategory[1] + $matchesAttribute[1] + $matchesProduct[1],
            'value' => $matchesCategory[2] + $matchesAttribute[2] + $matchesProduct[2]
        ];
    }

    /**
     * @param array $matches
     * @return array
     */
    protected function getReplaces($matches)
    {
        $replaceData = [];

        foreach ($matches['value'] as $matchKey => $matchValue) {
            $callback = "matcher" . ucfirst(trim($matches['type'][$matchKey]));
            $matchResult = call_user_func_array([$this, $callback], [$matchValue]);
            if (!empty($matchResult)) {
                $replaceData = array_merge_recursive($replaceData, $matchResult);
            }
        }
        return $replaceData;
    }

    /**
     * Returns stores ids.
     *
     * @param array $data Data from file.
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoresIds(array $data)
    {
        if (isset($data['store_code'])) {
            $storeCode = trim($data['store_code']);

            if (!isset($this->_storesId[$storeCode])) {

                /** @var \Magento\Store\Api\Data\StoreInterface $store */
                $store = $this->_storeManager->getStore($storeCode);
                $this->_storesId[$storeCode] = [$store->getId()];
            }

            return $this->_storesId[$storeCode];
        }

        return [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
    }
}
