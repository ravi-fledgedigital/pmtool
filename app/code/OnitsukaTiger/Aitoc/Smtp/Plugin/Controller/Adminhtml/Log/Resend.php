<?php
namespace OnitsukaTiger\Aitoc\Smtp\Plugin\Controller\Adminhtml\Log;

use Magento\Framework\Controller\Result\RedirectFactory;

class Resend
{
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * Resend constructor.
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function afterExecute(\Aitoc\Smtp\Controller\Adminhtml\Log\Resend $subject)
    {
        return $this->resultRedirectFactory->create()->setPath('aitoc_smtp/*/index');
    }
}
