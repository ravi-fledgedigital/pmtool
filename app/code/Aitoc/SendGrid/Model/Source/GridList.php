<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model\Source;

class GridList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        \Aitoc\SendGrid\Model\ApiWork $apiWork
    ) {
        $this->apiWork = $apiWork;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $lists = $this->apiWork->getContactLists();
        $result = [['value' => 0, 'label' => 'Global List']];
        foreach ($lists as $list) {
            $result[] = ['value' => $list['id'], 'label' => $list['name']];
        }

        return $result;
    }
}
