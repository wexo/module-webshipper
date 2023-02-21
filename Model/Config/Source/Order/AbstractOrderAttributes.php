<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class AbstractOrderAttributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    private $attributeFactory;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var SortOrder
     */
    private $sortOrder;
    /** 
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resourceConnection;
    

    public function __construct(
        \Magento\Framework\Api\SortOrder $sortOrder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->sortOrder = $sortOrder;
        $this->resourceConnection = $resourceConnection;
    }

    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default --')
        ];
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $this->sortOrder->setField('frontend_label');
        $this->sortOrder->setDirection('ASC');
        $this->searchCriteriaBuilder->addSortOrder($this->sortOrder);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeRepository = $this->attributeRepository->getList(
            'order',
            $searchCriteria
        );
        $options = [
            $this->getDefaultOption()
        ];
        foreach ($attributeRepository->getItems() as $items) {
            $options[] = [
                'value' => $items->getAttributeCode(),
                'label' => $items->getFrontendLabel()
            ];
        }

        // $reflectionClass = new \ReflectionClass(\Magento\Sales\Model\Order::class);
        // $constants = $reflectionClass->getConstants();
        // foreach($constants as $constant){
        //     $human = str_replace('_', ' ', $constant);
        //     $human = ucwords($human);
        //     $options[] = [
        //         'value' => $constant,
        //         'label' => $human
        //     ];
        // }

        
        $salesOrder = $this->resourceConnection->getConnection()->describeTable('sales_order');
        foreach(array_keys($salesOrder) as $attribute){
            $human = str_replace('_', ' ', $attribute);
            $human = ucwords($human);
            $options[] = [
                'value' => $attribute,
                'label' => $human
            ];
        }
        usort($options, function ($a, $b) {
            return strcmp($a['label'] ?? '', $b['label'] ?? '');
        });
        return $options;
    }   
}
