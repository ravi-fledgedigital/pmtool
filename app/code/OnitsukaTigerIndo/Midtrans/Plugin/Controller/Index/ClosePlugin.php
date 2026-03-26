<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerIndo\Midtrans\Plugin\Controller\Index;

use Midtrans\Snap\Controller\Index\Close;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;

/**
 * Class ClosePlugin
 * @package OnitsukaTigerIndo\Midtrans\Plugin\Controller\Index
 */
class ClosePlugin
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param Registry $registry
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        Registry $registry
    ){
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->registry = $registry;
    }

    /**
     * @param Close $subject
     * @param $resultPage
     * @return mixed
     */
    public function afterExecute(Close $subject, $resultPage)
    {
        if ($this->registry->registry('orders_canceled')) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        return $resultPage;
    }
}