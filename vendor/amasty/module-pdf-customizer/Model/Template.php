<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Template model
 *
 * @method \Amasty\PDFCustom\Model\ResourceModel\Template _getResource()
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Template extends \Magento\Email\Model\AbstractTemplate implements \Magento\Framework\Mail\TemplateInterface
{
    /**
     * @var array
     */
    protected $_vars = [];

    /**
     * Email filter factory
     *
     * @var \Magento\Email\Model\Template\FilterFactory
     */
    private $filterFactory;

    /**
     * @var Template\VariablesOptionRepository
     */
    private $variablesOptionRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\App\Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\UrlInterface $urlModel,
        \Magento\Email\Model\Template\FilterFactory $filterFactory,
        \Amasty\PDFCustom\Model\Template\VariablesOptionRepository $variablesOptionRepository,
        array $data = []
    ) {
        $this->filterFactory = $filterFactory;
        $this->variablesOptionRepository = $variablesOptionRepository;
        parent::__construct(
            $context,
            $design,
            $registry,
            $appEmulation,
            $storeManager,
            $assetRepo,
            $filesystem,
            $scopeConfig,
            $emailConfig,
            $templateFactory,
            $filterManager,
            $urlModel,
            $data
        );
    }

    /**
     * Initialize PDF template model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Amasty\PDFCustom\Model\ResourceModel\Template::class);
    }

    /**
     * Return template id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getTemplateId();
    }

    /**
     * Set id of template
     *
     * @param int $value
     * @return $this
     */
    public function setId($value)
    {
        return $this->setTemplateId($value);
    }

    /**
     * Getter for template type
     *
     * @return int
     */
    public function getType()
    {
        return self::TYPE_HTML;
    }

    /**
     * Parse variables string into array of variables
     *
     * @param string $variablesString
     * @return array
     */
    protected function _parseVariablesString($variablesString)
    {
        $variables = [];
        if ($variablesString && is_string($variablesString)) {
            $variablesString = str_replace("\n", '', $variablesString);
            $variables = json_decode($variablesString, true);
        }

        return $variables;
    }

    /**
     * Retrieve option array of variables
     *
     * @param boolean $withGroup if true wrap variable options in group
     * @return array
     */
    public function getVariablesOptionArray($withGroup = false)
    {
        $optionArray = [];
        $variables = $this->_parseVariablesString($this->getData('orig_template_variables'));
        if ($variables) {
            foreach ($variables as $value => $label) {
                $optionArray[] = ['value' => '{{' . $value . '}}', 'label' => __('%1', $label)];
            }
        }
        $optionArray = array_merge_recursive(
            $optionArray,
            $this->variablesOptionRepository->getAdditionalVariables($this)
        );
        if ($optionArray && $withGroup) {
            $optionArray = ['label' => __('Template Variables'), 'value' => $optionArray];
        }
        return $optionArray;
    }

    /**
     * Validate PDF template code
     *
     * @throws \Magento\Framework\Exception\MailException
     * @return $this
     */
    public function beforeSave()
    {
        $code = $this->getTemplateCode();
        if (empty($code)) {
            throw new \Magento\Framework\Exception\MailException(__('Please enter a template name.'));
        }
        if ($this->_getResource()->checkCodeUsage($this)) {
            throw new \Magento\Framework\Exception\MailException(__('Duplicate Of Template Name'));
        }
        parent::beforeSave();
        return $this;
    }

    /**
     * Get processed template
     *
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function processTemplate()
    {
        // Support theme fallback for PDF templates
        $isDesignApplied = $this->applyDesignConfig();

        $templateId = $this->getId();
        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $this->loadDefault($templateId);
        }

        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\MailException(
                __('Invalid transactional PDF code: %1', $templateId)
            );
        }

        // fix for 2.3.4 and newer
        $this->setData('is_legacy', 1);

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($this->_getVars());

        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }
        return $text;
    }

    /**
     * Get processed subject
     *
     * @return string
     */
    public function getSubject()
    {
        return '';
    }

    /**
     * Set template variables
     *
     * @param array $vars
     * @return $this
     */
    public function setVars(array $vars)
    {
        $this->_vars = $vars;
        return $this;
    }

    /**
     * Set template options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        return $this->setDesignConfig($options);
    }

    /**
     * @return \Magento\Email\Model\Template\FilterFactory
     */
    protected function getFilterFactory()
    {
        return $this->filterFactory;
    }

    /**
     * Retrieve template variables
     *
     * @return array
     */
    protected function _getVars()
    {
        return $this->_vars;
    }
}
