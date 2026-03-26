<?php

namespace OnitsukaTiger\RestockReports\Controller\Adminhtml\Restock;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Controller\Adminhtml\Export as ExportController;
use Magento\ImportExport\Model\LocalizedFileName;

class Download extends ExportController implements HttpGetActionInterface
{
    /**
     * Url to this controller
     */
    public const URL = "report/restock/download/";

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LocalizedFileName
     */
    private $localizedFileName;

    /**
     * DownloadFile constructor.
     * @param Action\Context $context
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param LocalizedFileName|null $localizedFileName
     */
    public function __construct(
        Action\Context $context,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        ?LocalizedFileName $localizedFileName = null
    ) {
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        parent::__construct($context);
        $this->localizedFileName =
            $localizedFileName ??
            ObjectManager::getInstance()->get(LocalizedFileName::class);
    }

    /**
     * Controller basic method implementation.
     *
     * @return \Magento\Framework\Controller\Result\Redirect | \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath("report/restock/index");
        $fileName = $this->getRequest()->getParam("filename");
        $exportDirectory = $this->filesystem->getDirectoryRead(
            DirectoryList::VAR_DIR
        );

        try {
            $path = $fileName;
            $directory = $this->filesystem->getDirectoryRead(
                DirectoryList::VAR_DIR
            );
            if ($directory->isFile($path)) {
                return $this->fileFactory->create(
                    $this->localizedFileName->getFileDisplayName($path),
                    ["type" => "filename", "value" => $path],
                    DirectoryList::VAR_DIR
                );
            }
            $this->messageManager->addErrorMessage(
                __("%1 is not a valid file", $fileName)
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $resultRedirect;
    }
}
