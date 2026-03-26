<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Chat;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Utils\FileUpload;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var FileUpload
     */
    private $fileUpload;

    public function __construct(
        ChatRepositoryInterface $chatRepository,
        CustomerRequestRepositoryInterface $requestRepository,
        FileUpload $fileUpload,
        Context $context
    ) {
        parent::__construct($context);
        $this->chatRepository = $chatRepository;
        $this->requestRepository = $requestRepository;
        $this->fileUpload = $fileUpload;
    }

    public function execute()
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if ($hash = $this->getRequest()->getParam('hash')) {
            try {
                $request = $this->requestRepository->getByHash($hash);
            } catch (\Exception $exception) {
                return $response->setData([]);
            }

            $message = $this->chatRepository->getEmptyMessageModel();
            $message->setMessage($this->getRequest()->getParam('message', ''))
                ->setIsManager(0)
                ->setIsRead(0)
                ->setRequestId($request->getRequestId())
                ->setCustomerId($request->getCustomerId())
                ->setName($request->getCustomerName());

            if ($files = $this->getRequest()->getParam('files')) {
                $messageFiles = [];

                foreach ($files as $file) {
                    $messageFile = $this->chatRepository->getEmptyMessageFileModel();
                    $messageFile->setFilepath($file[FileUpload::FILEHASH])
                        ->setFilename($file[FileUpload::FILENAME]);
                    $messageFiles[] = $messageFile;
                }
                $message->setMessageFiles($messageFiles);
            }
            $this->chatRepository->save($message);

            return $response->setData(['success' => true]);
        }

        return $response->setData([]);
    }
}
