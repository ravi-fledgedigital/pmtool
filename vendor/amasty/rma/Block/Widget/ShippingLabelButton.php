<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Widget;

use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Registry;

class ShippingLabelButton extends Template implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_Rma::widget/shippinglabel.phtml';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\Rma\Api\Data\RequestInterface|null
     */
    private $request = null;

    public function __construct(
        ConfigProvider $configProvider,
        Registry $registry,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $registry->registry(RegistryConstants::REQUEST_VIEW);
        $this->configProvider = $configProvider;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __($this->getData('label'));
    }

    /**
     * @return string
     */
    public function getDownloadUrl()
    {
        if (!$this->request) {
            return null;
        }
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . DIRECTORY_SEPARATOR . 'chat/downloadlabel',
            ['hash' => $this->request->getUrlHash(), 'request_id' => $this->request->getRequestId()]
        );
    }

    protected function _toHtml()
    {
        if (!$this->request || !$this->request->getShippingLabel()) {
            return '';
        }

        return parent::_toHtml();
    }
}
