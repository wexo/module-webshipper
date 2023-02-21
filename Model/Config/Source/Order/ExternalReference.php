<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalReference extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '0',
            'label' => __('-- Use Default (order_id) --')
        ];
    }
}
