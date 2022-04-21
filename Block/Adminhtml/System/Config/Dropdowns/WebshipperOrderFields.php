<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class WebshipperOrderFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractSelect
{
    public function getSourceOptions()
    {
        $webshipperFields = [
            ['label' => 'Att Contact', 'value' => 'att_contact'],
            ['label' => 'Company Name', 'value' => 'company_name'],
            ['label' => 'Address 1', 'value' => 'address_1'],
            ['label' => 'Address 2', 'value' => 'address_2'],
            ['label' => 'Country Code', 'value' => 'country_code'],
            ['label' => 'State', 'value' => 'state'],
            ['label' => 'Phone', 'value' => 'phone'],
            ['label' => 'Email', 'value' => 'email'],
            ['label' => 'Zip', 'value' => 'zip'],
            ['label' => 'City', 'value' => 'city'],
            ['label' => 'Vat No', 'value' => 'vat_no'],
            ['label' => 'Address Type', 'value' => 'address_type'],
            ['label' => 'Ext Location', 'value' => 'ext_location'],
            ['label' => 'Voec', 'value' => 'voec'],
            ['label' => 'Eori', 'value' => 'eori'],
            ['label' => 'Sprn', 'value' => 'sprn'],
            ['label' => 'Personal Customs No', 'value' => 'personal_customs_no'],
            ['label' => 'Company Customs Numbers', 'value' => 'company_customs_numbers'],
        ];
        return [ ...parent::getSourceOptions(), ...$webshipperFields ];
    }
}
