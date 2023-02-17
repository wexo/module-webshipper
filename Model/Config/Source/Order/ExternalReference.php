<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalReference extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default (order_id) --')
        ];
    }
}
