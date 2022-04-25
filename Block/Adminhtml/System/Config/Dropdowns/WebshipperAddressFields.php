<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class WebshipperAddressFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractSelect
{
    public function getSourceOptions()
    {
        $webshipperFields = [
            [
                'label' => 'Webshipper Address Fields',
                'value' => [
                    'att_contact' => 'Att Contact', 
                    'company_name' => 'Company Name', 
                    'address_1' => 'Address 1', 
                    'address_2' => 'Address 2', 
                    'country_code' => 'Country Code', 
                    'state' => 'State', 
                    'phone' => 'Phone', 
                    'email' => 'Email', 
                    'zip' => 'Zip', 
                    'city' => 'City', 
                    'vat_no' => 'Vat No', 
                    'address_type' => 'Address Type', 
                    'ext_location' => 'Ext Location', 
                    'voec' => 'Voec', 
                    'eori' => 'Eori', 
                    'sprn' => 'Sprn', 
                    'personal_customs_no' => 'Personal Customs No', 
                    'company_customs_numbers' => 'Company Customs Numbers', 
                ]
            ]
        ];
        return [ ...parent::getSourceOptions(), ...$webshipperFields ];
    }
}
