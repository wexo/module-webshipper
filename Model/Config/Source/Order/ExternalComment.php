<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalComment extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Use Default (none) --')
        ];
    }
}
