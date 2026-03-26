<?php
declare(strict_types=1);

namespace OnitsukaTiger\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{

    public $customerSession;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
    }

    /** 
     * @return bool
     */
    public function iscustomerLogin()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }
}
