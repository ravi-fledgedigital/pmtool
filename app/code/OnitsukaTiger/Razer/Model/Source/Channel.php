<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Razer\Model\Source;
use OnitsukaTiger\Razer\Helper\Data;

class Channel implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var Data
     */
    public $method;

    public function __construct(
        Data          $paymentHelper,
    )
    {
        $this->method = $paymentHelper;
    }

     /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->method->getActiveChannelsFromApi();
    }


    /*
     * Get options in "key-value" format
	 * @return array
	 */
    public function toArray()
    {
        $choose = [];
        $activeChannelApis = $this->method->getActiveChannelsFromApi();
        foreach($activeChannelApis as $channel){
            $choose[$channel['value']] = $channel['label'] ;
        }

        return $choose;
    }
}
