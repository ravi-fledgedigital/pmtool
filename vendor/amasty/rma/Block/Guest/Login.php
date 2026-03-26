<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Guest;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Validation\GuestLogin\FieldValidatorProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

class Login extends Template implements BlockInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var FieldValidatorProvider
     */
    private $fieldValidatorProvider;

    /**
     * @var HttpContext
     */
    private $httpContext;

    public function __construct(
        ConfigProvider $configProvider,
        Context $context,
        HttpContext $httpContext,
        array $data = [],
        ?FieldValidatorProvider $fieldValidatorProvider = null
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->fieldValidatorProvider = $fieldValidatorProvider
            ?? ObjectManager::getInstance()->get(FieldValidatorProvider::class);
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return !($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH));
    }

    /**
     * @return string
     */
    public function getTypeSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            Select::class
        )->setData(
            ['id' => 'quick_search_type_id', 'class' => 'select guest-select']
        )->setName(
            'oar_type'
        )->setOptions(
            $this->_getFormOptions()
        )->setExtraParams(
            'onchange="showIdentifyBlock(this.value);"'
        );

        return $select->getHtml();
    }

    /**
     * @return array [['value' => '', 'label' => ''], [...]]
     */
    protected function _getFormOptions()
    {
        $options = $this->getData('identifymeby_options');
        if ($options === null) {
            $options = [];
            $options[] = ['value' => 'email', 'label' => 'Email Address'];
            $options[] = ['value' => 'zip', 'label' => 'ZIP Code'];
            $this->setData('identifymeby_options', $options);
        }

        return $options;
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl(
            $this->configProvider->getUrlPrefix() . '/guest/loginPost',
            ['_secure' => true]
        );
    }

    public function isFieldAvailable(string $field): bool
    {
        $validator = $this->fieldValidatorProvider->getValidatorByType($field);
        if (null !== $validator) {
            return $validator->isFieldAvailable();
        }

        return true;
    }
}
