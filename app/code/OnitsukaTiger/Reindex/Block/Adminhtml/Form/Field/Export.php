<?php

namespace OnitsukaTiger\Reindex\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Escaper;
use Magento\Backend\Model\UrlInterface;

/**
 * Class Export
 * @package OnitsukaTiger\Reindex\Block\Adminhtml\Form\Field
 */
class Export extends AbstractElement
{
    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * Export constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        UrlInterface $backendUrl,
        array $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->backendUrl = $backendUrl;
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        /** @var \Magento\Backend\Block\Widget\Button $buttonBlock  */
        $buttonBlock = $this->getForm()->getParent()->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');

        $params = ['website' => $buttonBlock->getRequest()->getParam('website')];

        $url = $this->backendUrl->getUrl('onitsuka/system/export', $params);
        $data = [
            'label' => __('Export File (Xml)'),
            'onclick' => 'setLocation(\'' .$url.'\')',
            'class' => '',
        ];
        $html = $buttonBlock->setData($data)->toHtml();
        return $html;
    }
}
