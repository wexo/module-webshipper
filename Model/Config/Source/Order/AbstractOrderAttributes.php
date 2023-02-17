<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class AbstractOrderAttributes implements \Magento\Framework\Data\OptionSourceInterface
{
    public $defaultOption = [
        'value' => '',
        'label' => __('-- Use Default --')
    ];
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

    public function __construct(
        \Magento\Framework\Api\SortOrder $sortOrder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->sortOrder = $sortOrder;
    }

    public function getDefaultOption()
    {
        return $this->defaultOption;
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
        uksort($options, function ($a, $b) {
            $a = mb_strtolower($a);
            $b = mb_strtolower($b);
            return strcmp($a, $b);
        });
        return $options;
    }   
}
