<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class ExternalComment extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default (external_comment) --')
        ];
    }
}
