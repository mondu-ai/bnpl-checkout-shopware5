<?php

namespace Mond1SWR5\Bootstrap\Attributes;

use Shopware\Models\Order\Order;

class OrderAttributes extends AbstractAttributes
{
    protected function getEntityClass()
    {
        return Order::class;
    }

    protected function createUpdateAttributes()
    {
        $this->crudService->update($this->tableName, 'mondu_reference_id', 'string');
        $this->crudService->update($this->tableName, 'mondu_state', 'string');
        $this->crudService->update($this->tableName, 'mondu_invoice_iban', 'string');
        $this->crudService->update($this->tableName, 'mondu_external_invoice_number', 'string');
        $this->crudService->update($this->tableName, 'mondu_duration', 'integer');
        $this->crudService->update($this->tableName, 'mondu_payment_method', 'string');
        $this->crudService->update($this->tableName, 'mondu_merchant_company_name', 'string');
        $this->crudService->update($this->tableName, 'mondu_authorized_net_term', 'integer');
    }

    protected function uninstallAttributes()
    {
        $attributeList = [
            'mondu_reference_id',
            'mondu_state',
            'mondu_invoice_iban',
            'mondu_external_invoice_number',
            'mondu_duration',
            'mondu_payment_method',
            'mondu_merchant_company_name',
            'mondu_authorized_net_term'
        ];

        foreach ($attributeList as $attributeCode) {
            $this->deleteAttribute($attributeCode);
        }
    }
}
