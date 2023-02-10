<?php

namespace Wexo\Webshipper\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class ExportOrderButton
{
    public function beforeSetLayout(OrderView $subject)
    {
        $subject->addButton(
            'webshipper_order_export',
            [
                'label' => __('Export order to Webshipper'),
                'class' => __('webshipper-order-export'),
                'id' => 'webshipper-order-export',
                'onclick' => 'setLocation(\'' .
                    $subject->getUrl('wexo_webshipper/order/export') .
                    '\')'
            ]
        );
    }
}