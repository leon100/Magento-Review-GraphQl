# Mageleon_ReviewGraphQl Module

This module provides a GraphQL query that retrieves the latest product review data for a given product id. It only selects data from the database that is requested in the GraphQL query.

## Usage

To use this module, you can execute the following GraphQL query:

```graphql
{
    last_product_review (id: 1) {
        review_id
        created_at
        title
        detail
        nickname
    }
}
