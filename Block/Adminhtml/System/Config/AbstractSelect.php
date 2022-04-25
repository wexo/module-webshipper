<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\Html\Select;

abstract class AbstractSelect extends Select
{
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    public function getSourceOptions()
    {
        return [
            [
                'label' => 'Custom (use static as value)',
                'value' => ['static' => 'Static']
            ]
        ];
    }

    public function getExtraParams()
    {
        return 'style="width:200px"';
    }
}
