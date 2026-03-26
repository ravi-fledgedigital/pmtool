<?php
declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Uploader;

use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;

class UploaderPlugin
{

    /**
     * @param NotProtectedExtension $subject
     * @param $result
     * @param null $store
     * @return array|mixed
     */
    public function afterGetProtectedFileExtensions(NotProtectedExtension $subject, $result, $store = null)
    {
        if (is_array($result)) {
            unset($result['xml']);
        }
        return $result;
    }
}
