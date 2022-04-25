<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class WebshipperOrderFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractSelect
{
    public function getSourceOptions()
    {
        $webshipperFields = [
            ['label' => 'Order Channel Id', 'value' => 'order_channel_id'],
            ['label' => 'Status', 'value' => 'status'],
            ['label' => 'Ext Ref', 'value' => 'ext_ref'],
            ['label' => 'Visible Ref', 'value' => 'visible_ref'],
            ['label' => 'Drop Point', 'value' => 'drop_point'],
            ['label' => 'Original Shipping', 'value' => 'original_shipping'],
            ['label' => 'Order Lines', 'value' => 'order_lines'],
            ['label' => 'Delivery Address', 'value' => 'delivery_address'],
            ['label' => 'Sender Address', 'value' => 'sender_address'],
            ['label' => 'Billing Address', 'value' => 'billing_address'],
            ['label' => 'Sold From Address', 'value' => 'sold_from_address'],
            ['label' => 'Currency', 'value' => 'currency'],
            ['label' => 'Internal Comment', 'value' => 'internal_comment'],
            ['label' => 'External Comment', 'value' => 'external_comment'],
            ['label' => 'Error Message', 'value' => 'error_message'],
            ['label' => 'Slip', 'value' => 'slip'],
            ['label' => 'Base64', 'value' => 'base64'],
            ['label' => 'Updated At', 'value' => 'updated_at'],
            ['label' => 'Created At', 'value' => 'created_at'],
            ['label' => 'Lock State', 'value' => 'lock_state'],
            ['label' => 'Source', 'value' => 'source'],
            ['label' => 'Tags', 'value' => 'tags'],
            ['label' => 'Error Class', 'value' => 'error_class'],
            ['label' => 'Slip Printed', 'value' => 'slip_printed'],
            ['label' => 'Label Printed', 'value' => 'label_printed'],
            ['label' => 'Create Shipment Automatically', 'value' => 'create_shipment_automatically'],
            ['label' => 'Latest Status Event', 'value' => 'latest_status_event'],
        ];
        return [ ...parent::getSourceOptions(), ...$webshipperFields ];
    }
}