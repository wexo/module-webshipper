<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class WebshipperOrderLineFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractSelect
{
    public function getSourceOptions()
    {
        $webshipperFields = [
            ['label' => 'Sku', 'value' => 'sku'],
            ['label' => 'Description', 'value' => 'description'],
            ['label' => 'Quantity', 'value' => 'quantity'],
            ['label' => 'Location', 'value' => 'location'],
            ['label' => 'Tarif Number', 'value' => 'tarif_number'],
            ['label' => 'Country Of Origin', 'value' => 'country_of_origin'],
            ['label' => 'Unit Price', 'value' => 'unit_price'],
            ['label' => 'Package Id', 'value' => 'package_id'],
            ['label' => 'Discounted Unit Price', 'value' => 'discounted_unit_price'],
            ['label' => 'Discount Value', 'value' => 'discount_value'],
            ['label' => 'Discount Type', 'value' => 'discount_type'],
            ['label' => 'Vat Percent', 'value' => 'vat_percent'],
            ['label' => 'Order Id', 'value' => 'order_id'],
            ['label' => 'Status', 'value' => 'status'],
            ['label' => 'Ext Ref', 'value' => 'ext_ref'],
            ['label' => 'Weight', 'value' => 'weight'],
            ['label' => 'Weight Unit', 'value' => 'weight_unit'],
            ['label' => 'Created At', 'value' => 'created_at'],
            ['label' => 'Updated At', 'value' => 'updated_at'],
            ['label' => 'Is Virtual', 'value' => 'is_virtual'],
            ['label' => 'Dangerous Goods Details', 'value' => 'dangerous_goods_details'],
        ];
        return [ ...parent::getSourceOptions(), ...$webshipperFields ];
    }
}
