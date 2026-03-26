<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Chat;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Model\Request\CustomerRequestRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class DeleteMessage extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CustomerRequestRepository
     */
    private $requestRepository;

    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    public function __construct(
        CustomerRequestRepository $requestRepository,
        ChatRepositoryInterface $chatRepository,
        Context $context
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
                    && !$message->isManager() && !$message->isSystem()
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
