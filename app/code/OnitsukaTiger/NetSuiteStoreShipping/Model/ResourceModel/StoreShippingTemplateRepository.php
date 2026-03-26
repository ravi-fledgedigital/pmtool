<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Model\ResourceModel;

use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;
use OnitsukaTiger\NetSuiteStoreShipping\Model\Source\PluginAddType;

/**
 * Class StoreShippingTemplateRepository
 * @package OnitsukaTiger\NetSuiteStoreShipping\Model\ResourceModel
 */
class StoreShippingTemplateRepository extends TemplateRepository
{
    /**
     * @param $storeId
     * @param $customerGroupId
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPackingListTemplateId($storeId, $customerGroupId)
    {
        return $this->getTemplateIdByParams(
            PluginAddType::TYPE_PACKING_LIST,
            $storeId,
            $customerGroupId
        );
    }
}
