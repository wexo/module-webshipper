<?php
 
namespace Wexo\Webshipper\Block\Adminhtml\System\Config;
 
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\WebshipperAddressFields;
use Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoFields;

class AbstractFrontendModel extends AbstractFieldArray
{
    public $selectOptions;
    public $webshipperFields = false;
    public $magentoFields = false;
 
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

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
 
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $selectFieldData = $row->getSelectField();
        if ($selectFieldData !== null) {
            $options['option_' . $this->getSelectFieldOptions()->calcOptionHash($selectFieldData)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
 
    private function getSelectFieldOptions()
    {
        if (!$this->selectOptions) {
            $this->selectOptions = $this->getLayout()->createBlock(
                \Magento\Framework\View\Element\Html\Select::class,
                '',
                // ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->selectOptions;
    }
 
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);

        // $script = '<script type="text/javascript">
        //         require(["jquery", "jquery/ui", "mage/calendar"], function (jq) {
        //             jq(function(){
        //                 function bindDatePicker() {
        //                     setTimeout(function() {
        //                         jq(".daterecuring").datepicker( { dateFormat: "mm/dd/yy" } );
        //                     }, 50);
        //                 }
        //                 bindDatePicker();
        //                 jq("button.action-add").on("click", function(e) {
        //                     bindDatePicker();
        //                 });
        //             });
        //         });
        //     </script>';
        // $html .= $script;
        return $html;
    }
}