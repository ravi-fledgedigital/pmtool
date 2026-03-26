<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Block\Adminhtml;

class Template extends \Magento\Email\Block\Adminhtml\Template
{
    /**
     * fix for Magento 2.2.3
     * Template list
     *
     * @var string
     */
    protected $_template = 'Magento_Email::template/list.phtml';

    /**
     * Get URL for create new email template
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('amasty_pdf/*/new');
    }
}
