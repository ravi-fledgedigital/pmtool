<?php

namespace Seoulwebdesign\Kpostcode\Block;

class PostCode extends \Magento\Framework\View\Element\Template
{
    protected $_objectManager;

    /**
     * PostCode constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $data);
    }

    public function getLanguages()
    {
        $resolver = $this->_objectManager->get('Magento\Framework\Locale\Resolver');
        $countryCode = $resolver->getLocale();
        return substr($countryCode, 0, 2);
    }
}
