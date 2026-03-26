<?php

namespace OnitsukaTiger\Rma\Controller\Adminhtml\Order;

use Magento\Sales\Controller\Adminhtml\Order\AddComment as BaseAddComment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;

class AddComment extends BaseAddComment
{
    public function execute()
    {
        /** @var $order Order */
        $order = $this->_initOrder();
        if (!$order) {
            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }
        try {
            $data = $this->getRequest()->getPost('history');
            if (empty($data['comment']) && $data['status'] == $order->getDataByKey('status')) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The comment is missing. Enter and try again.')
                );
            }

            $notify = $data['is_customer_notified'] ?? false;
            $visible = $data['is_visible_on_front'] ?? false;

            if ($notify && !$this->_authorization->isAllowed(self::ADMIN_SALES_EMAIL_RESOURCE)) {
                $notify = false;
            }

            $history = $order->addStatusHistoryComment($data['comment'], $data['status']);
            $history->setIsVisibleOnFront($visible);
            $history->setIsCustomerNotified($notify);
            $history->setIsAdmin(true);
            $history->save();

            $comment = trim(strip_tags($data['comment']));

            $order->save();
            /** @var OrderCommentSender $orderCommentSender */
            $orderCommentSender = $this->_objectManager
                ->create(\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender::class);

            $orderCommentSender->send($order, $notify, $comment);

            return $this->resultPageFactory->create();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We cannot add order history.')];
        }
        if (is_array($response)) {
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData($response);
            return $resultJson;
        }

    }


}
