<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class EnablePDFCustom extends Field
{
    /**
     * @var \Amasty\PDFCustom\Model\ComponentChecker
     */
    private $componentChecker;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Amasty\PDFCustom\Model\ComponentChecker $componentChecker,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->componentChecker = $componentChecker;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->componentChecker->isComponentsExist()) {
            $element->setDisabled(true);
            $element->setComment($this->componentChecker->getComponentsErrorMessage());
        }

        return parent::render($element);
    }
}
