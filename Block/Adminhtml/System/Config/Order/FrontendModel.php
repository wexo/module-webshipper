<?php
namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Order;

use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoOrderFields;
use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\WebshipperOrderFields;

class FrontendModel extends \Wexo\Webshipper\Block\Adminhtml\System\Config\AbstractFrontendModel
{
    public function getWebshipperFields()
    {
        if (!$this->webshipperFields) {
            $this->webshipperFields = $this->getLayout()->createBlock(
                WebshipperOrderFields::class,
                ''
            );
        }
        return $this->webshipperFields;
    }

    public function getMagentoFields()
    {
        if (!$this->magentoFields) {
            $this->magentoFields = $this->getLayout()->createBlock(
                MagentoOrderFields::class,
                ''
            );
        }
        return $this->magentoFields;
    }
}
