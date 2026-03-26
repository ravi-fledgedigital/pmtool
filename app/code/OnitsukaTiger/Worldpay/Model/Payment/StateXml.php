<?php

namespace OnitsukaTiger\Worldpay\Model\Payment;

class StateXml extends \Sapient\Worldpay\Model\Payment\StateXml
{
    /**
     * @var SimpleXMLElement
     */
    private $_xml;

    public function __construct($xml)
    {
        $this->_xml = $xml;
        parent::__construct($xml);
    }

    /**
     * Retrieve status node from xml
     *
     * @return xml
     */
    private function getStatusNode()
    {
        if (isset($this->_xml->reply)) {
            return $this->_xml->reply->orderStatus;
        }

        return $this->_xml->notify->orderStatusEvent;
    }

    /**
     * Retrieve journal reference from xml
     *
     * @return bool
     */
    public function isRefusedEvent(): bool
    {
        $statusNode = $this->getStatusNode();
        $journalNodes = $statusNode->journal;
        $isRefusedEvent = false;
        foreach ($journalNodes as $journal) {
            if ($journal['journalType'] == \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_REFUSED) {
                $isRefusedEvent = true;
                break;
            }
        }

        return $isRefusedEvent;
    }
}
