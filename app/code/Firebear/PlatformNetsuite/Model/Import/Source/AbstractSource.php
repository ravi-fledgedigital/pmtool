<?php
 /**
  * Copyright © Firebear Studio, Inc. All rights reserved.
  * See COPYING.txt for license details.
  */
namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Magento\Framework\Exception\LocalizedException;

/**
 * Netsuite abstract source
 */
class AbstractSource extends \Firebear\ImportExport\Model\Import\Source\Json
{
    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\Order
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
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Order $gateway,
        \Magento\Framework\App\RequestInterface $request,
        $data = null
    ) {
        $this->gateway = $gateway;
        $this->request = $request;
        $this->parseEntities($data);
    }

    /**
     * Set config data
     *
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Retrieve config data
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param $data
     * @return array|bool
     * @throws \Exception
     */
    protected function parseEntities($data)
    {
        $this->entities = [];
        $data['page'] = $this->request->getParam('offset', null);

        if (!empty($data['get_only_first_page'])) {
            $data['page'] = 1;
        }

        if (!$data['page']) {
            if (!empty($data['start_page'])) {
                if ($data['start_page'] > 1) {
                    $data['page'] = 1;
                    $this->entities = $this->gateway->uploadPartSource($data);
                }
                $data['page'] = $data['start_page'];
            } else {
                $data['page'] = 1;
            }

            if (!empty($data['end_page'])) {
                for ($i = $data['page']; $i <= $data['end_page']; $i++) {
                    $entities = $this->gateway->uploadPartSource($data);
                    foreach ($entities as $entity) {
                        $this->entities[] = $entity;
                    }
                    $data['page']++;
                }
            } else {
                while ($entities = $this->gateway->uploadPartSource($data)) {
                    if (!empty($entities)) {
                        foreach ($entities as $entity) {
                            $this->entities[] = $entity;
                        }
                        $data['page']++;
                    } else {
                        break;
                    }
                }
            }
        } else {
            if (!empty($data['end_page']) && $data['page'] > $data['end_page']) {
                $message = __('The import was stopped on page: %1', $data['end_page']);
                throw new LocalizedException($message);
            }

            $this->entities = $this->gateway->uploadPartSource($data);
        }

        if (empty($this->entities)) {
            $message = __('Error loading data from API. The page: %1', $data['page']);
            throw new LocalizedException($message);
        }
        return $this->entities;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getNextRow()
    {
        $this->_lock = false;
        return parent::_getNextRow();
    }
}
