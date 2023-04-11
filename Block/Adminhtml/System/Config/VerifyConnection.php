<?php
namespace Wexo\Webshipper\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class VerifyConnection extends Field
{
    protected $_template = 'Wexo_Webshipper::system/config/verify_connection.phtml';

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        \Magento\Backend\Model\UrlInterface $url,
        array $data = []
    ) {
        $this->url = $url;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    
    public function getButtonHtml()
    {
        
        $store = $this->getRequest()->getParam('store');
        $url = $this->url->getUrl('wexo_webshipper/verify/index',[
            'store' => $store
        ]);
        return $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData(
                [
                    'id' => 'btn_id', 
                    'label' => __('Verify Connection'),
                    'onclick' => sprintf("location.href = '%s'", $url) . '; return false;'
                ]
            )
            ->toHtml();
    }
    
}
