<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\SenderAddress;

use \Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\WebshipperAddressFields;
use \Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoFields;

class FrontendModel extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractFrontendModel
{
    public function getWebshipperFields()
    {
        if (!$this->webshipperFields) {
            $this->webshipperFields = $this->getLayout()->createBlock(
                WebshipperAddressFields::class,
                ''
            );
        }
        return $this->webshipperFields;
    }
    public function getMagentoFields()
    {
        if (!$this->magentoFields) {
            $this->magentoFields = $this->getLayout()->createBlock(
                MagentoFields::class,
                ''
            );
        }
        return $this->magentoFields;
    }
}
