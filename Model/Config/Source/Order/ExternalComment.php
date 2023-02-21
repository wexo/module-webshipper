<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalComment extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '0',
            'label' => __('-- Use Default (customer_note) --')
        ];
    }
}
