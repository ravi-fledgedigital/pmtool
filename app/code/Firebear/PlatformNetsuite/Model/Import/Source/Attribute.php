<?php

namespace Firebear\PlatformNetsuite\Model\Import\Source;

use Magento\Framework\Exception\LocalizedException;

class Attribute extends \Firebear\ImportExport\Model\Import\Source\Json
{
    /**
     * @var \Firebear\PlatformNetsuite\Model\Source\Gateway\Attribute
     */
    protected $gateway;

    /**
     * Config data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Attribute constructor.
     *
     * @param Gateway\Attribute $gateway
     * @param null $data
     * @throws \Exception
     */
    public function __construct(
        \Firebear\PlatformNetsuite\Model\Import\Source\Gateway\Attribute $gateway,
        $data = null
    ) {
        $this->gateway = $gateway;
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
        $this->entities = $this->gateway->uploadSource($data);
        if (empty($this->entities)) {
            $message = __('Error loading data from API. The page: %1', $data['page']);
            throw new LocalizedException($message);
        }
        return $this->entities;
    }
}
