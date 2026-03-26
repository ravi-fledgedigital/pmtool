<?php
namespace OnitsukaTiger\Sorting\Plugin\Catalog\Block\Product\ProductList;

class Toolbar
{

    /**
     * @var \OnitsukaTiger\Fixture\Helper\Data
     */
    private $helper;


    /**
     * Toolbar constructor.
     * @param \OnitsukaTiger\Fixture\Helper\Data $helper
     */
    public function __construct(
        \OnitsukaTiger\Fixture\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Toolbar $subject
     * @param $result
     * @return mixed
     */
    public function beforeSetCollection(\Magento\Catalog\Block\Product\ProductList\Toolbar $subject, $collection)
    {
        if($this->helper->getConfig('amsorting/customize_sort_default/enable_automatic')){
            if($this->helper->getConfig('amsorting/customize_sort_default/attribute')){
                $orderby = $this->helper->getConfig('amsorting/customize_sort_default/order_by') ? $this->helper->getConfig('amsorting/customize_sort_default/order_by') : 'asc';
                $collection->setOrder($this->helper->getConfig('amsorting/customize_sort_default/attribute'), $orderby);
            }
        }
        return [$collection];
    }
}
