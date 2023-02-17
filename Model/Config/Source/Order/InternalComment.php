<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class InternalComment extends AbstractOrderAttributes
{

    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default (internal_comment) --')
        ];
    }
}
