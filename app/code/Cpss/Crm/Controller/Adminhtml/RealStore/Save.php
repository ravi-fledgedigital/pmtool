<?php
namespace Cpss\Crm\Controller\Adminhtml\RealStore;

use Cpss\Crm\Helper\Customer;

class Save extends \Magento\Backend\App\Action
{
    protected $gridFactory;
    protected $customerHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Cpss\Crm\Model\RealStoreFactory $gridFactory,
        Customer $customerHelper
    ) {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->customerHelper = $customerHelper;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->_redirect('admincrm/realstore/add');
            return;
        }
        try {
            $rowData = $this->gridFactory->create();
            if (!empty($data['shop_password'])) {
                $hashedPassword = $this->hashPassword($data['shop_password']);
                $data['shop_password_hash'] = $hashedPassword;
                $data['access_token'] = $this->generateAccessToken($hashedPassword);
            } else {
                $data['shop_password_hash'] = null;
                $data['access_token'] = null;
            }
            
            $rowData->setData($data);
            $rowData->save();
            $this->messageManager->addSuccess(__('Row data has been successfully saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('admincrm/realstore/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Cpss_Crm::save');
    }

    private function hashPassword($password)
    {
        return $this->customerHelper->generateAccessToken($password);
    }

    private function generateAccessToken($passwordHash)
    {
        return $this->customerHelper->generateAccessToken($passwordHash);
    }
}
