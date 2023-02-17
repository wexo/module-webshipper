<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class VisibleReference extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default (increment_id) --')
        ];
    }
}
