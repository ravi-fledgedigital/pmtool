<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Widget;

use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Registry;

class PackingSlipButton extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_Rma::widget/packingslip.phtml';

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __($this->getData('label'));
    }
}
