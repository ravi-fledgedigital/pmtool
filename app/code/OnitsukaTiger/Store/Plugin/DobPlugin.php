<?php

namespace OnitsukaTiger\Store\Plugin;

use Magento\Customer\Block\Widget\Dob;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Store\Helper\Data;

class DobPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $helperStore;

    /**
     * Timezone Plugin constructor.
     * @param StoreManagerInterface $storeManager
     * @param Data $helperStore
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helperStore
    ) {
        $this->storeManager = $storeManager;
        $this->helperStore = $helperStore;
    }


    /**
     * @param Dob $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetDateFormat(
        Dob $subject,
        $result
    ) {
        $result = $this->helperStore->formatDateOfDob($this->storeManager->getStore()->getId());
        return $result;
    }
}
