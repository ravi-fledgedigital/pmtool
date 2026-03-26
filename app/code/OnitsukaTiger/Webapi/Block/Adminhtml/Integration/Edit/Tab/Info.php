<?php
namespace OnitsukaTiger\Webapi\Block\Adminhtml\Integration\Edit\Tab;

use Magento\Integration\Controller\Adminhtml\Integration;

class Info extends \Magento\Integration\Block\Adminhtml\Integration\Edit\Tab\Info
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    private $systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
        $this->systemStore = $systemStore;
    }

    /**
     * Set form id prefix, declare fields for integration info
     *
     * @return \Magento\Integration\Block\Adminhtml\Integration\Edit\Tab\Info
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix(self::HTML_ID_PREFIX);
        $integrationData = $this->_coreRegistry->registry(Integration::REGISTRY_KEY_CURRENT_INTEGRATION);
        $this->_addGeneralFieldset($form, $integrationData);
        $this->_addDetailsFieldset($form, $integrationData);
        $this->addFieldStoreView($form);
        $form->setValues($integrationData);
        $this->setForm($form);
        return $this;
    }

    /**
     * Add fieldset with integration details. This fieldset is available for existing integrations only.
     *
     * @param \Magento\Framework\Data\Form $form
     * @return void
     */
    public function addFieldStoreView($form)
    {
        $fieldset = $form->addFieldset('third_fieldset', ['legend' => __('Scope')]);
        $fieldset->addField(
            'store_ids',
            'multiselect',
            [
                'name'     => 'store_ids[]',
                'label'    => __('Store View'),
                'title'    => __('Store View'),
                'required' => false,
                'values'   => $this->systemStore->getStoreValuesForForm(true, true),
            ]
        );
    }
}
