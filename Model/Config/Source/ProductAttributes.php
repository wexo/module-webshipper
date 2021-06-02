<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Order Statuses source model
 */

namespace Wexo\Webshipper\Model\Config\Source;

use Magento\Framework\Api\SortOrder;

/**
 * Class Status
 * @api
 * @since 100.0.2
 */
class ProductAttributes implements \Magento\Framework\Option\ArrayInterface
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

    public function __construct(
        SortOrder $sortOrder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->sortOrder = $sortOrder;
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
            'catalog_product',
            $searchCriteria
        );
        $options = [];
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
