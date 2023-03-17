<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalReference extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Use Default (order_id) --')
        ];
    }
}
