<?php

namespace Mond1SWR5\Services\Webhook;

class WebhookStruct
{

    /**
     * @var string
     */
    private $topic;

    /**
     * @var string
     */
    private $externalReferenceId;

    private $orderUid;

    private $invoiceUid;

    private $orderState;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string | null
     */
    private $viban;
    /**
     * @var string
     */
    private $creationTime;

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var array
     */
    private $resource;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return string | null
     */
    public function getViban() {
        return $this->viban;
    }
    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return string
     */
    public function getExternalReferenceId()
    {
        return $this->externalReferenceId;
    }

    public function getOrderUid()
    {
        return $this->orderUid;
    }

    public function getInvoiceUid()
    {
        return $this->getInvoiceUid();
    }

    public function getOrderState()
    {
        return $this->orderState;
    }

    /**
     * @return string
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @return array
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param array $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return WebhookStruct
     */
    public static function fromArray(array $data)
    {
        $result = new self();
        $result->setEventType($data['event_type']);

        $result->setTopic($data['topic']);
        $result->setExternalReferenceId($data['external_reference_id']);
        $result->setOrderUid($data['order_uuid']);
        $result->setOrderState($data['order_state']);

        $result->setCreationTime($data['create_time']);
        $result->setId($data['id']);
        $result->setViban($data['viban']);
        $result->setResourceType($data['resource_type']);
        $result->setSummary($data['summary']);
        $result->setResource($data['resource']);
        $result->setInvoiceUid($data['invoice_uuid']);
        return $result;
    }

    /**
     * Converts this object to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return \get_object_vars($this);
    }

    private function setInvoiceUid($uid)
    {
        $this->invoiceUid = $uid;
    }
    /**
     * @param string $id
     */
    private function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $viban
     * @return void
     */
    public function setViban($viban) {
        $this->viban = $viban;
    }
    /**
     * @param string $creationTime
     */
    private function setCreationTime($creationTime)
    {
        $this->creationTime = $creationTime;
    }

    /**
     * @param string $resourceType
     */
    private function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;
    }

    /**
     * @param string $eventType
     */
    private function setEventType($eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * @param string $summary
     */
    private function setSummary($summary)
    {
        $this->summary = $summary;
    }

    /**
     * @param string $topic
     */
    private function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * @param string $topic
     */
    private function setExternalReferenceId($referenceId)
    {
        $this->externalReferenceId = $referenceId;
    }
    private function setOrderUid($uid)
    {
        $this->orderUid = $uid;
    }
    private function setOrderState($orderState)
    {
        $this->orderState = $orderState;
    }
}
