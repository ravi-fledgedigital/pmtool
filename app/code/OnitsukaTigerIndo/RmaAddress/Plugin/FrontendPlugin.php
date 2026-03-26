<?php

namespace OnitsukaTigerIndo\RmaAddress\Plugin;

use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory;
use Amasty\Rma\Controller\FrontendRma;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Cookie\HashChecker;
use Amasty\Rma\Utils\FileUpload;
use Closure;
use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;

class FrontendPlugin extends FrontendRma
{
    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ChatRepositoryInterface
     */
    private ChatRepositoryInterface $chatRepository;

    /**
     * @var HashChecker
     */
    private HashChecker $hashChecker;

    /**
     * @var FileUpload
     */
    private FileUpload $fileUpload;

    /**
     * @var RequestCustomFieldInterfaceFactory
     */
    private RequestCustomFieldInterfaceFactory $customFieldFactory;

    /**
     * @param Session $customerSession
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param ChatRepositoryInterface $chatRepository
     * @param HashChecker $hashChecker
     * @param FileUpload $fileUpload
     * @param RequestCustomFieldInterfaceFactory $customFieldFactory
     */
    public function __construct(
        Session                            $customerSession,
        ConfigProvider                     $configProvider,
        StoreManagerInterface              $storeManager,
        ChatRepositoryInterface            $chatRepository,
        HashChecker                        $hashChecker,
        FileUpload                         $fileUpload,
        RequestCustomFieldInterfaceFactory $customFieldFactory
    ) {
        parent::__construct(
            $customerSession,
            $configProvider,
            $storeManager,
            $chatRepository,
            $hashChecker,
            $fileUpload,
            $customFieldFactory
        );
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->chatRepository = $chatRepository;
        $this->hashChecker = $hashChecker;
        $this->fileUpload = $fileUpload;
        $this->customFieldFactory = $customFieldFactory;
    }

    /**
     * Around Save New Return Message
     *
     * @param FrontendRma $subject
     * @param Closure $proceed
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @param string $comment
     * @param array $files
     * @return void
     */
    public function aroundSaveNewReturnMessage(FrontendRma $subject, Closure $proceed, $request, $comment, $files)
    {
        $message = $this->chatRepository->getEmptyMessageModel();
        $message->setIsRead(0)
            ->setMessage($comment)
            ->setCustomerId($this->customerSession->getCustomerId())
            ->setName($request['customer_name'] ?? $request->getCustomerName())
            ->setRequestId($request['request_id'] ?? $request->getRequestId());
        if ($files) {
            $messageFiles = [];
            foreach ($files as $file) {
                $messageFile = $this->chatRepository->getEmptyMessageFileModel();
                $messageFile->setFilepath($file[FileUpload::FILEHASH])
                    ->setFilename($file[FileUpload::FILENAME]);
                $messageFiles[] = $messageFile;
            }
            $message->setMessageFiles($messageFiles);
        }

        try {
            $this->chatRepository->save($message, false);
        } catch (\Exception $e) {
            null;
        }
    }
}
