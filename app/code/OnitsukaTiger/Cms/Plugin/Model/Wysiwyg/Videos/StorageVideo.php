<?php
namespace OnitsukaTiger\Cms\Plugin\Model\Wysiwyg\Videos;

use Magento\Cms\Model\Wysiwyg\Images\Storage as Subject;
use Magento\MediaStorage\Model\File\UploaderFactory;
use \Magento\MediaStorage\Model\File\Uploader;
class StorageVideo
{
    /**
     * Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;

    public function __construct(
        UploaderFactory $uploaderFactory
    ) {
        $this->_uploaderFactory = $uploaderFactory;
    }
    public function aroundUploadFile(Subject $subject, callable $proceed,$targetPath, $type = null)
    {
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $subject->getAllowedExtensions($type);
        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

        $result['cookie'] = [
            'name' => $subject->getSession()->getName(),
            'value' => $subject->getSession()->getSessionId(),
            'lifetime' => $subject->getSession()->getCookieLifetime(),
            'path' => $subject->getSession()->getCookiePath(),
            'domain' => $subject->getSession()->getCookieDomain(),
        ];
        return $result;
    }
}
