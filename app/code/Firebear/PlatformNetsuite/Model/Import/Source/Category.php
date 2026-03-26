<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Magento\Framework\Exception\LocalizedException;

/**
 * Netsuite category source
 */
class Category extends AbstractSource
{
    const DEFAULT_CATEGORY_NAME = 'Default Category';

    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\Category
     */
    protected $gateway;

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Product constructor.
     * @param Gateway\Product $gateway
     * @param \Magento\Framework\App\RequestInterface $request
     * @param null $data
     * @throws \Exception
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Category $gateway,
        \Magento\Framework\App\RequestInterface $request,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->request = $request;
        $this->parseEntities($data);
    }

    /**
     * @param $data
     * @return array|bool
     * @throws \Exception
     */
    protected function parseEntities($data)
    {
        $this->entities = parent::parseEntities($data);
        if (!empty($this->entities)) {
            foreach ($this->entities as $key => $entity) {
                $categoryPath = explode(':', $entity['fullName']);
                $categoryPath = implode(':', array_map('trim', $categoryPath));
                $this->entities[$key]['fullName'] = self::DEFAULT_CATEGORY_NAME .
                    ':'. $categoryPath;
            }
        }
        return $this->entities;
    }
}
