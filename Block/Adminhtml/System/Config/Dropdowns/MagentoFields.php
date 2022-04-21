<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class MagentoFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractSelect
{
    public function getSourceOptions()
    {
        $magentoFields = [
            [
                'label' => 'Store Information', 
                'value' => [
                    'store_name' => 'Name',
                    'store_phone' => 'Phone',
                    'store_country' => 'Country',
                    'store_region' => 'Region',
                    'store_zip' => 'Zip/Postal Code',
                    'store_city' => 'City',
                    'store_street1' => 'Street1',
                    'store_street2' => 'Street2',
                    'store_vat' => 'Vat',
                ]
            ],[
                'label' => 'Session Information',
                'value' => [
                    'session_name' => 'Name',
                    'session_email' => 'Email',
                ]
            ],[
                'label' => 'Shipping Information',
                'value' => [
                    'shipping_name' => 'Name',
                    'shipping_firstname' => 'First Name',
                    'shipping_lastname' => 'Last Name',
                    'shipping_email' => 'Email',
                    'shipping_phone' => 'Phone',
                    'shipping_country' => 'Country',
                    'shipping_region' => 'Region',
                    'shipping_zip' => 'Zip/Postal Code',
                    'shipping_city' => 'City',
                    'shipping_street1' => 'Street1',
                    'shipping_street2' => 'Street2',
                    'shipping_vat' => 'Vat'
                ]
            ],[
                'label' => 'Billing Information',
                'value' => [
                    'billing_name' => 'Name',
                    'billing_firstname' => 'First Name',
                    'billing_lastname' => 'Last Name',
                    'billing_email' => 'Email',
                    'billing_phone' => 'Phone',
                    'billing_country' => 'Country',
                    'billing_region' => 'Region',
                    'billing_zip' => 'Zip/Postal Code',
                    'billing_city' => 'City',
                    'billing_street1' => 'Street1',
                    'billing_street2' => 'Street2',
                    'billing_vat' => 'Vat'
                ]
            ]
        ];
        return [ ...parent::getSourceOptions(), ...$magentoFields ];
    }
}
