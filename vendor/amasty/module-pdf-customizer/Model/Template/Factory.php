<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */
/**
 * PDF Template Factory
 */
namespace Amasty\PDFCustom\Model\Template;

class Factory implements \Magento\Framework\Mail\Template\FactoryInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * @var string
     */
    protected $instanceName = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Amasty\PDFCustom\Model\Template::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Returns the PDF template associated with the identifier.
     *
     * @param string $identifier
     * @param null|string $namespace
     * @return \Amasty\PDFCustom\Model\Template
     */
    public function get($identifier, $namespace = null)
    {
        return $this->objectManager->create(
            $namespace ? $namespace : $this->instanceName,
            ['data' => ['template_id' => $identifier]]
        );
    }
}
