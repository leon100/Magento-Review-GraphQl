<?php
/**
 * Copyright © Ihor Leontiuk. All rights reserved.
 */
declare(strict_types=1);

namespace Mageleon\ReviewGraphQl\Model\Resolver;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

class Reviews implements ResolverInterface
{
    private const QUERY_NAME = 'last_product_review';

    /**
     * Constructor for Reviews resolver.
     *
     * @param StoreManagerInterface $storeManager
     * @param ReviewCollectionFactory $reviewCollection
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ReviewCollectionFactory $reviewCollection,
    ) {
    }

    /**
     * Resolves the GraphQL query field.
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return DataObject
     * @throws NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    : DataObject
    {
        // Get the current store ID
        $currentStoreId = $this->storeManager->getStore()->getId();

        // Create a new review collection
        $collection = $this->reviewCollection->create();

        // Build the select query
        $select = $collection
            ->addStoreFilter($currentStoreId)
            ->addStatusFilter(Review::STATUS_APPROVED)
            ->addEntityFilter('product', $args['id'])
            ->setDateOrder()
            ->getSelect();

        // Clear select columns and select only requested in GraphQL query
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);

        // Process the field nodes from the query
        foreach ($info->fieldNodes as $node) {
            if ($node->name->value !== self::QUERY_NAME) {
                continue;
            }
            $columns = [];
            foreach ($node->selectionSet->selections as $selectionNode) {
                if ($selectionNode->name->value === 'created_at') {
                    $columns[] = 'main_table.' . $selectionNode->name->value;
                } else {
                    $columns[] = 'detail.' . $selectionNode->name->value;
                }
            }
        }

        // Add selected columns to the query
        if (isset($columns)) {
            $select->columns($columns);
        }

        // Retrieve the first item from the collection
        return $collection->getFirstItem();
    }
}
