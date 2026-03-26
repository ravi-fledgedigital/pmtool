<?php

namespace OnitsukaTigerIndo\RmaAddress\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param \Amasty\Rma\Model\ConfigProvider $configProvider
     */
    public function __construct(
        Context $context,
        private \Amasty\Rma\Model\ConfigProvider $configProvider
    ) {
        parent::__construct($context);
    }

    /**
     * Get max file size.
     *
     * @return int
     */
    public function getFileSizeValue()
    {
        return $this->configProvider->getMaxFileSize();
    }
}
