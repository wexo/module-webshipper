<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\DeliveryAddress;

class FrontendModel extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractFrontendModel
{
    private $webshipperFields = false;
    private $magentoFields = false;

    protected function _prepareToRender()
    {

        $this->addColumn(
            'webshipper_field',
            [
                'label' => __('Webshipper Field'),
                'class' => 'required-entry',
                'renderer' => $this->getWebshipperFields(),
            ]
        );

        $this->addColumn(
            'magento_field',
            [
                'label' => __('Magento Field'),
                'class' => 'required-entry',
                'renderer' => $this->getMagentoFields(),
            ]
        );

        $this->addColumn(
            'static_field',
            [
                'label' => __('Static'),
                'style' => 'width:300px',
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add More');
    }
}
