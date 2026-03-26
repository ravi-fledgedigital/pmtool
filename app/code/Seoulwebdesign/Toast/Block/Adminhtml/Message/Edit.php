<?php
namespace Seoulwebdesign\Toast\Block\Adminhtml\Message;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * The constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * The initial
     */
    protected function _construct()
    {
        $this->_objectId = 'message_id';
        $this->_blockGroup = 'Seoulwebdesign_Toast';
        $this->_controller = 'adminhtml_message';

        parent::_construct();

        if ($this->_isAllowedAction('Seoulwebdesign_Toast::message')) {
            $this->buttonList->update('save', 'label', __('Save Message'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Seoulwebdesign_Toast::message')) {
            $this->buttonList->update('delete', 'label', __('Delete Message'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('toast_message')->getId()) {
            return __(
                "Edit Message '%1'",
                $this->escapeHtml($this->_coreRegistry->registry('toast_message')->getTitle())
            );
        } else {
            return __('New Message');
        }
    }

    /**
     * Get is allow action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get Save And Continue Url
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('seoulwebdesign_toast/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}
