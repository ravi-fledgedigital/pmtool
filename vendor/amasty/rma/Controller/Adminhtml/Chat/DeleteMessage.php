<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Chat;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class DeleteMessage extends Action
{
    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        ChatRepositoryInterface $chatRepository,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
        $this->chatRepository = $chatRepository;
    }

    public function execute()
    {
        $hash= $this->getRequest()->getParam('hash');
        $messageId= $this->getRequest()->getParam('message_id');

        $result = [];

        if ($hash && $messageId) {
            try {
                $request = $this->requestRepository->getByHash($hash);
                $message = $this->chatRepository->getById($messageId);
                if ($message->getRequestId() === $request->getRequestId()
                    && $message->isManager()
                ) {
                    $this->chatRepository->delete($message);
                    $result['success'] = true;
                }
            } catch (LocalizedException $exception) {
                $result['error'] = $exception->getMessage();
            }
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($result);
    }
}
