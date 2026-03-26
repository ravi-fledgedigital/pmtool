<?php

declare(strict_types=1);

namespace OnitsukaTiger\CategoryModelImage\Plugin\Controller\Adminhtml\Category;


use Magento\Framework\App\RequestInterface;

class Save
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var RequestInterface
     */
    protected $request;

    protected $processor;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Action\Context $contextAction
     * @param RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $contextAction,
        \OnitsukaTiger\CategoryModelImage\Model\CategoryModelImage\Media\Processor $processor,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->processor = $processor;
        $this->_request = $contextAction->getRequest();
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function beforeExecute(\Magento\Catalog\Controller\Adminhtml\Category\Save $subject)
    {
        $categoryData = $this->request->getPost('category', []);
        if(array_key_exists('media_gallery',$categoryData)){
            if(array_key_exists('images',$categoryData['media_gallery'])){
                $this->processor->proccessCategoryGalerry($subject->getRequest()->getParam('entity_id'),$categoryData['media_gallery']['images'],$subject->getRequest()->getParam('store_id'));
            }
        }
    }
}
