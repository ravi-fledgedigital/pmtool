<?php
namespace OnitsukaTiger\Fixture\Model\Cms\Page;

/**
 * Class Converter
 * @package OnitsukaTiger\Fixture\Model\Cms\Page
 */
class Converter
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $_storeManager Store manager.
     */
    protected $_storeManager;

    /**
     * @var array $_storesId Stored stores codes with ID.
     */
    protected $_storesId = [];

    /**
     * Converter constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
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
