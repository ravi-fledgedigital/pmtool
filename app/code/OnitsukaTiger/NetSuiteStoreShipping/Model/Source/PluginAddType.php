<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Model\Source;

/**
 * Class PluginAddType
 * @package OnitsukaTiger\NetSuiteStoreShipping\Model\Source
 */
class PluginAddType
{
    const TYPE_PACKING_LIST = 5;

    /**
     * @param \Amasty\PDFCustom\Model\Source\PlaceForUse $subject
     * @param $result
     * @return array
     */
    public function afterToOptionArray(\Amasty\PDFCustom\Model\Source\PlaceForUse $subject, $result)
    {
        $packingListType = ['value' => self::TYPE_PACKING_LIST, 'label' => __('Packing List')];
        $result[] = $packingListType;

        return $result;
    }
}
