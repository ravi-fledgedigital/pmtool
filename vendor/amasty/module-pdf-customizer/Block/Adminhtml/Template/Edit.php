<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Block\Adminhtml\Template;

use Magento\Backend\Block\Widget\ContainerInterface;

/**
 * Adminhtml PDF template edit block
 *
 * @method array getTemplateOptions()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Edit extends \Magento\Email\Block\Adminhtml\Template\Edit implements ContainerInterface
{
    /**
     * Template file
     *
     * @var string
     */
    protected $_template = 'Amasty_PDFCustom::template/edit.phtml';

    /**
     * @var \Magento\Store\Model\System\Store
     */
    private $systemStoreSource;

    /**
     * @var \Amasty\PDFCustom\Model\Source\PlaceForUse
     */
    private $placeForUseSource;

    /**
     * @var \Amasty\PDFCustom\Model\Source\CustomerGroup
     */
    private $customerGroupSource;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Menu\Config $menuConfig,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList,
        \Magento\Backend\Block\Widget\Button\ToolbarInterface $toolbar,
        \Magento\Store\Model\System\Store $systemStoreSource,
        \Amasty\PDFCustom\Model\Source\PlaceForUse $placeForUseSource,
        \Amasty\PDFCustom\Model\Source\CustomerGroup $customerGroupSource,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $jsonEncoder,
            $registry,
            $menuConfig,
            $configStructure,
            $emailConfig,
            $jsonHelper,
            $buttonList,
            $toolbar,
            $data
        );
        $this->systemStoreSource = $systemStoreSource;
        $this->placeForUseSource = $placeForUseSource;
        $this->customerGroupSource = $customerGroupSource;
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "window.location.href = '" . $this->getBackUrl() . "'",
                'class' => 'back'
            ]
        );
        $this->buttonList->add(
            'reset',
            ['label' => __('Reset'), 'onclick' => 'window.location.href = window.location.href']
        );

        if ($this->getEditMode()) {
            $this->buttonList->add(
                'delete',
                [
                    'label' => __('Delete Template'),
                    'data_attribute' => [
                        'role' => 'template-delete',
                    ],
                    'class' => 'delete'
                ]
            );
        }
        $this->buttonList->add(
            'previewpdf',
            [
                'label' => __('Preview Template as PDF'),
                'data_attribute' => [
                    'role' => 'template-previewpdf',
                ]
            ]
        );
        $this->buttonList->add(
            'save',
            [
                'label' => __('Save Template'),
                'data_attribute' => [
                    'role' => 'template-save',
                ],
                'class' => 'save primary save-template'
            ]
        );
        $this->buttonList->add(
            'load',
            [
                'label' => __('Load Template'),
                'data_attribute' => [
                    'role' => 'template-load',
                ],
                'type' => 'button',
                'class' => 'save'
            ],
            0,
            0,
            null
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        $this->addChild(
            'form',
            \Amasty\PDFCustom\Block\Adminhtml\Template\Edit\Form::class,
            [
                'email_template' => $this->getEmailTemplate(),
                // trick for not overload __construct because of changed params in magento 2.3
                'systemStoreSource' => $this->systemStoreSource,
                'placeForUseSource' => $this->placeForUseSource,
                'customerGroupSource' => $this->customerGroupSource
            ]
        );

        return $this;
    }

    /**
     * Retrieve PDF template model
     *
     * @return \Amasty\PDFCustom\Model\Template
     */
    public function getEmailTemplate()
    {
        return $this->getData('email_template');
    }

    /**
     * Get default templates as options array
     *
     * @return array
     */
    protected function _getDefaultTemplatesAsOptionsArray()
    {
        $options = parent::_getDefaultTemplatesAsOptionsArray();
        foreach ($options as $key => $option) {
            if ($option['value'] != '' && $option['group'] != 'Amasty_PDFCustom') {
                unset($options[$key]);
            } else {
                continue;
            }
        }
        return $options;
    }

    /**
     * Return action url for form
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('amasty_pdf/template');
    }

    /**
     * Return action url for form
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('amasty_pdf/template/save', ['_current' => true]);
    }

    /**
     * Return preview action url for form
     *
     * @return string
     */
    public function getPreviewPdfUrl()
    {
        return $this->getUrl('amasty_pdf/template/previewpdf');
    }

    /**
     * Return preview action url for form
     *
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->getUrl('amasty_pdf/template/preview');
    }

    /**
     * Return delete url for customer group
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('amasty_pdf/template/delete', ['_current' => true]);
    }

    /**
     * Load template url
     *
     * @return string
     */
    public function getLoadUrl()
    {
        return $this->getUrl('amasty_pdf/template/defaultTemplate');
    }
}
