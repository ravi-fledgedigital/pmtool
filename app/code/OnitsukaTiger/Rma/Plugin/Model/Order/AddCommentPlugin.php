<?php

namespace OnitsukaTiger\Rma\Plugin\Model\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use OnitsukaTiger\Rma\Helper\OrderStatusHistory;

class AddCommentPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var HistoryFactory
     */
    protected $_orderHistoryFactory;

    /**
     * @var OrderStatusHistory
     */
    protected $helperStatusHistory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param HistoryFactory $historyFactory
     * @param OrderStatusHistory $orderStatusHistory
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager,
        HistoryFactory $historyFactory,
        OrderStatusHistory $orderStatusHistory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_orderHistoryFactory = $historyFactory;
        $this->helperStatusHistory = $orderStatusHistory;
    }

    /**
     * @param Order $subject
     * @param \Closure $proceed
     * @param $comment
     * @param $status
     * @param $isVisibleOnFront
     * @return \Closure
     * @throws NoSuchEntityException
     */
    public function aroundAddCommentToStatusHistory(
        Order $subject,
        \Closure $proceed,
        $comment,
        $status = false,
        $isVisibleOnFront = false)
    {
        $enable = $this->helperStatusHistory->getIsShowHistoryOfMemo();
        $result = $proceed;

        if (!$enable) {
            return $result;
        }
        if (false === $status) {
            $status = $subject->getStatus();
        } elseif (true === $status) {
            $status = $subject->getConfig()->getStateDefaultStatus($subject->getState());
        } else {
            $subject->setStatus($status);
        }
        $history = $this->_orderHistoryFactory->create()->setStatus(
            $status
        )->setComment(
            $comment
        )->setEntityName(
            'order'
        )->setIsVisibleOnFront(
            $isVisibleOnFront
        )->setIsAdmin(
            true
        );
        $subject->addStatusHistory($history);
        return $history;
    }
}
