<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Chat;

use Amasty\Rma\Utils\FileUpload;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class DeleteTemp extends \Magento\Framework\App\Action\Action
{
    /**
     * @var FileUpload
     */
    private $fileUpload;

    public function __construct(
        Context $context,
        FileUpload $fileUpload
    ) {
        parent::__construct($context);
        $this->fileUpload = $fileUpload;
    }

    public function execute()
    {
        $file = $this->getRequest()->getParam('file');

        if (!$file) {
            return null;
        }
        $result = [];

        try {
            $this->fileUpload->deleteTemp($file['filehash'], $file['extension']);
        } catch (\Exception $e) {
            $result[] = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($result);
    }
}
