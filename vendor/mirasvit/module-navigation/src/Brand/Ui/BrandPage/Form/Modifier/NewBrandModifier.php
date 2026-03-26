<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\Brand\Ui\BrandPage\Form\Modifier;


use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;


class NewBrandModifier implements ModifierInterface
{
    private $arrayManager;

    private $urlBuilder;

    public function __construct(
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder
    ) {
        $this->arrayManager = $arrayManager;
        $this->urlBuilder = $urlBuilder;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->createNewModal($meta);

        return $meta;
    }

    /**
     * @param array $meta
     * @return array
     */
    private function createNewModal($meta)
    {
        return $this->arrayManager->set(
            'create_brand_modal',
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'isTemplate'    => false,
                            'componentType' => 'modal',
                            'options'       => [
                                'title' => __('New Brand'),
                            ],
                            'imports'       => [
                                'state' => '!index=create_brand:responseStatus',
                            ],
                        ],
                    ],
                ],
                'children'  => [
                    'create_brand' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label'            => 'Create Brand',
                                    'componentType'    => 'container',
                                    'component'        => 'Magento_Ui/js/form/components/insert-form',
                                    'dataScope'        => '',
                                    'update_url'       => $this->urlBuilder->getUrl('mui/index/render'),
                                    'render_url'       => $this->urlBuilder->getUrl(
                                        'mui/index/render_handle',
                                        [
                                            'handle'  => 'brand_option_create',
                                            'buttons' => 1,
                                        ]
                                    ),
                                    'autoRender'       => false,
                                    'ns'               => 'brand_option_form',
                                    'externalProvider' => 'brand_option_form.brand_option_form_data_source',
                                    'toolbarContainer' => '${ $.parentName }',
                                    'formSubmitType'   => 'ajax',
                                ],
                            ],
                        ],
                        'children' => []
                    ],
                ],
            ]
        );
    }
}
