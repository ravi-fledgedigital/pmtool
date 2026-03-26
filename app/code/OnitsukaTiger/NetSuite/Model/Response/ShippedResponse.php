<?php


namespace OnitsukaTiger\NetSuite\Model\Response;


class ShippedResponse extends \OnitsukaTiger\NetSuite\Model\Response\Response implements \OnitsukaTiger\NetSuite\Api\Response\ShippedResponseInterface
{

    protected $shippingId;
    protected $invoiceNo;
    /**
     * @var string
     */
    protected $awb;

    public function __construct(
        bool $success,
        string $shippingId,
        string $invoiceNo,
        string $awb
    )
    {
        $this->shippingId = $shippingId;
        $this->invoiceNo = $invoiceNo;
        $this->awb = $awb;

        parent::__construct($success);
    }

    public function getShippingId()
    {
        return $this->shippingId;
    }

    public function setShippingId(string $shippingId)
    {
        $this->shippingId = $shippingId;
    }

    public function getInvoiceNo()
    {
        return $this->invoiceNo;
    }

    public function setInvoiceNo(string $invoiceNo)
    {
        $this->invoiceNo = $invoiceNo;
    }

    public function getAwb()
    {
        return $this->awb;
    }

    public function setAwb(string $awb)
    {
        $this->awb = $awb;
    }

    public function toString()
    {
        return json_encode([
            'success' => $this->getSuccess(),
            'shipping_id' => $this->getShippingId(),
            'invoice_no' => $this->getInvoiceNo(),
            'awb' => $this->getAwb()
        ]);
    }
}
