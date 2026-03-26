<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Label;

use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class ImageUploader extends Action
{
    private $imageUploader;

    public function __construct(
        \Magento\Catalog\Model\ImageUploader $imageUploader,
        Context $context
    ){
        parent::__construct($context);

        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $fieldName  = $this->getRequest()->getParam('param_name');
        $file       = $this->getRequest()->getFiles()->toArray();
        $keys       = preg_split("/\]\[|\[|\]/", $fieldName, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($keys as $key) {
            if (isset($file[$key])) {
                $file = $file[$key];
            }
        }

        try {
            $labelConfig          = ObjectManager::getInstance()->get(ConfigProvider::class);
            $result               = $this->imageUploader->saveFileToTmpDir($file);
            $path                 = $this->imageUploader->moveFileFromTmp($result['file'], true);
            $result['image_path'] = str_replace('cataloglabel/', '', $path);
            $result['url']        = $labelConfig->getBaseMediaUrl() . '/' . $result['image_path'];
            $result['name']       = $result['image_path'];
        } catch (\Exception $exception) {
            $result = [
                'error'     => $exception->getMessage(),
                'errorcode' => $exception->getCode()
            ];
        }

        return $resultJson->setData($result);
    }
}
