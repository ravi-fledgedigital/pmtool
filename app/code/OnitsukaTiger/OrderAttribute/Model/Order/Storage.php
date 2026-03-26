<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Order;

use Magento\Backend\Model\Session;

class Storage
{
    /**
     * @var Session
     */
    private $session;

    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

    /**
     * @param $orderIds
     * @return $this
     */
    public function setOrderIds($orderIds)
    {
        $this->session->setOrderIds($orderIds);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderIds()
    {
        return $this->session->getOrderIds();
    }
}
