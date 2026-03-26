<?php

namespace Seoulwebdesign\Toast\Block\Adminhtml\Message\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Seoulwebdesign\Toast\Model\Message;
use Seoulwebdesign\Toast\Model\MessageFieldAbstract;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var FieldFactory
     */
    protected $_fieldFactory;
    /**
     * @var Message
     */
    protected $messageToast;
    /**
     * @var array
     */
    protected $messageFields;

    /**
     * The constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param FieldFactory $fieldFactory
     * @param Message $messageToast
     * @param array $messageFields
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        FieldFactory $fieldFactory,
        Message $messageToast,
        array $messageFields = [],
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_fieldFactory = $fieldFactory;
        $this->messageToast = $messageToast;
        $this->messageFields = $messageFields;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare Form
     *
     * @return Form
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var Message $model */
        $model = $this->_coreRegistry->registry('toast_message');

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );

        $form->setHtmlIdPrefix('message_');

        $infoFieldset = $form->addFieldset(
            'base_info_fieldset',
            ['legend' => __('Message Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getMessageId()) {
            $infoFieldset->addField('message_id', 'hidden', ['name' => 'message_id']);
        }

        $infoFieldset->addField(
            'toast_id',
            'text',
            [
                'name' => 'toast_id',
                'label' => __('Toast Message ID'),
                'title' => __('Toast Message ID'),
                'required' => true
            ]
        );

        $infoFieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => $model->getAvailableStatuses()
            ]
        );

        $messageActions = $infoFieldset->addField(
            'send_message_action',
            'select',
            [
                'label' => __('When to send Message'),
                'title' => __('When to send Message'),
                'name' => 'send_message_action',
                'required' => true,
                'options' => $model->getAvailableSendActions()
            ]
        );

        $variableFieldset = $form->addFieldset(
            'var_info_fieldset',
            ['legend' => __('Message Variable Information'), 'class' => 'fieldset-wide']
        );
        $dependenceBlock = $this->getLayout()->createBlock(
            Dependence::class
        );

        $fixedVariables = [
            ['id' => 'var_shop_url', 'label' => 'Shop URL variable name'],
            ['id' => 'var_shop_name', 'label' => 'Shop Name variable name']
        ];
        foreach ($fixedVariables as $fixedVariable) {
            $variableFieldset->addField(
                $fixedVariable['id'],
                'text',
                [
                    'name' => $fixedVariable['id'],
                    'label' => __($fixedVariable['label']),
                    'title' => __($fixedVariable['label'])
                ]
            );
        }

        foreach ($this->messageFields as $messageField) {
            /** @var MessageFieldAbstract $messageField */
            $messageFieldVariables = $messageField->getAvailableVariables();

            $messageFieldRef = $messageField->getRefFieldList();

            foreach ($messageFieldVariables as $messageFieldVariable) {
                $fparams = [];
                $fparams = [
                    'name' => $messageFieldVariable['id'],
                    'label' => __($messageFieldVariable['label']),
                    'title' => __($messageFieldVariable['label'])
                ];
//            if (isset($orderVariable['comment'])) {
//                $fparams['after_element_html'] = '<p class="nm"><small>' . $orderVariable['comment'] . '</small></p>';
//            }
                $element = $variableFieldset->addField($messageFieldVariable['id'], 'text', $fparams);
                $refField = $this->_fieldFactory->create(
                    [
                        'fieldData' => [
                            'value' => implode(',', $messageFieldRef),
                            'separator' => ','
                        ],
                        'fieldPrefix' => ''
                    ]
                );
                $this->setChild(
                    'form_after',
                    $dependenceBlock
                        ->addFieldMap("{$form->getHtmlIdPrefix()}{$element->getId()}", $element->getId())
                        ->addFieldMap("{$form->getHtmlIdPrefix()}{$messageActions->getId()}", $messageActions->getId())
                        ->addFieldDependence($element->getId(), $messageActions->getId(), $refField)
                );
            }
        }

        if (!$model->getId()) {
            $model->setData('status', '1');
            $model->setData('send_message_action', Message::ORDER_PLACED);
        }

        $data = $model->getData();
        if ($model->getJsonVar()) {
            $jsonVar = json_decode($model->getJsonVar(), true);
            if ($jsonVar) {
                foreach ($jsonVar as $key => $value) {
                    $data[$key] = $value;
                }
            }
        }

        $form->setValues($data);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
