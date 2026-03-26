<?php
/** phpcs:ignoreFile */
declare(strict_types=1);

namespace OnitsukaTigerIndo\Midtrans\Plugin\Controller\Index;

use Midtrans\Snap\Controller\Index\Finish;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;

/**
 * Class FinishPlugin
 * @package OnitsukaTigerIndo\Midtrans\Plugin\Controller\Index
 */
class FinishPlugin
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
     * @param Finish $subject
     * @param $resultPage
     * @return mixed
     */
    public function afterExecute(Finish $subject, $resultPage)
    {
        if ($this->registry->registry('transaction_status') && $this->registry->registry('order_id')) {
            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
        }

        return $resultPage;
    }
}
