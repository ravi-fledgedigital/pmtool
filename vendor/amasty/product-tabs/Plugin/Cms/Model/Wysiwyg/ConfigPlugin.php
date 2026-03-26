<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Plugin\Cms\Model\Wysiwyg;

class ConfigPlugin
{
    public const TINY_MCE_4 = 'mage/adminhtml/wysiwyg/tiny_mce/tinymce4Adapter';

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @var \Amasty\Base\Model\Serializer
     */
    private $encoder;

    /**
     * @var \Amasty\CustomTabs\Model\Attribute\ItemsProvider
     */
    private $itemsProvider;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $url;

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Amasty\Base\Model\Serializer $encoder,
        \Magento\Backend\Model\UrlInterface $url,
        \Amasty\CustomTabs\Model\Attribute\ItemsProvider $itemsProvider
    ) {
        $this->assetRepo = $assetRepo;
        $this->encoder = $encoder;
        $this->itemsProvider = $itemsProvider;
        $this->url = $url;
    }

    /**
     * @param \Magento\Cms\Model\Wysiwyg\Config $subject
     * @param \Closure $proceed
     * @param array $data
     *
     * @return \Magento\Framework\DataObject|mixed
     */
    public function aroundGetConfig(
        \Magento\Cms\Model\Wysiwyg\Config $subject,
        \Closure $proceed,
        $data = []
    ) {
        $amastyTab = isset($data['is_amasty_tab']) && $data['is_amasty_tab'];
        if ($amastyTab) {
            $data['add_variables'] = true;
            $data['add_widgets'] = true;
        }
        $config = $proceed($data);
        if ($amastyTab) {
            $config = $this->applyAttributePlugin($config);
            if ($subject->getConfig()->getData('activeEditorPath') == self::TINY_MCE_4) {
                $this->updateTinyMce4($config);
            }
        }

        return $config;
    }

    /**
     * @param \Magento\Framework\DataObject $config
     *
     * @return \Magento\Framework\DataObject
     */
    protected function applyAttributePlugin(\Magento\Framework\DataObject $config)
    {
        $settings = $this->getWysiwygPluginSettings($config);
        return $config->addData($settings);
    }

    /**
     * Prepare variable wysiwyg config
     *
     * @param \Magento\Framework\DataObject $config
     * @return array
     */
    public function getWysiwygPluginSettings($config)
    {
        $variableConfig = [];
        $onclickParts = [
            'search' => ['html_id'],
            'subject' => 'AmastyProductTabsAttributePlugin.loadChooser(\'' .
                $this->getAttributesWysiwygActionUrl() .
                '\', \'{{html_id}}\');',
        ];
        $attributeWysiwyg = [
            [
                'name' => 'amcustomtabs_attribute',
                'src' => $this->getWysiwygJsPluginSrc(),
                'options' => [
                    'title' => __('Insert Attribute...'),
                    'url' => $this->getAttributesWysiwygActionUrl(),
                    'onclick' => $onclickParts,
                    'class' => 'plugin amcustomtabs-add_attribute',
                    'placeholders' => $this->getAttributesWysiwygData()
                ],
            ],
        ];
        $configPlugins = (array) $config->getData('plugins');
        $variableConfig['plugins'] = array_merge($configPlugins, $attributeWysiwyg);
        return $variableConfig;
    }

    /**
     * Return url of action to get attributes
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAttributesWysiwygActionUrl()
    {
        return $this->url->getUrl('mui/index/render', ['namespace' => 'amcustomtabs_attributes_modal']);
    }

    /**
     * Return url to wysiwyg plugin
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getWysiwygJsPluginSrc()
    {
        $editorPluginJs = 'Amasty_CustomTabs::js/wysiwyg/tiny_mce/plugins/editor_plugin.js';

        return $this->assetRepo->getUrl($editorPluginJs);
    }

    /**
     * Return attribute related wysiwyg data
     *
     * @return bool|string
     */
    protected function getAttributesWysiwygData()
    {
        $attributesData = $this->itemsProvider->execute();

        return $this->encoder->serialize($attributesData);
    }

    /**
     * @param \Magento\Framework\DataObject $result
     */
    private function updateTinyMce4($result)
    {
        $settings = $result->getData('settings');

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['menubar'] = true;
        $settings['image_advtab'] = true;

        // @codingStandardsIgnoreStart
        $settings['plugins'] = 'advlist autolink code colorpicker directionality hr imagetools link media noneditable paste print table textcolor toc visualchars anchor charmap codesample contextmenu fullpage help image insertdatetime lists nonbreaking pagebreak preview searchreplace template textpattern visualblocks wordcount magentovariable magentowidget';
        $settings['toolbar1'] = 'magentovariable magentowidget | formatselect | styleselect | fontsizeselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';
        $settings['toolbar2'] = ' undo redo  | link anchor table charmap | image media insertdatetime | widget | searchreplace visualblocks  help | hr pagebreak';
        // @codingStandardsIgnoreEnd

        $result->setData('settings', $settings);
    }
}
