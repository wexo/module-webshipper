<?php
namespace Wexo\Webshipper\Block\Adminhtml\System\Config\OrderLine;

use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoOrderLineFields;
use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\WebshipperOrderLineFields;

class FrontendModel extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractFrontendModel
{
    public function getWebshipperFields()
    {
        if (!$this->webshipperFields) {
            $this->webshipperFields = $this->getLayout()->createBlock(
                WebshipperOrderLineFields::class,
                ''
            );
        }
        return $this->webshipperFields;
    }
    public function getMagentoFields()
    {
        if (!$this->magentoFields) {
            $this->magentoFields = $this->getLayout()->createBlock(
                MagentoOrderLineFields::class,
                ''
            );
        }
        return $this->magentoFields;
    }
}
