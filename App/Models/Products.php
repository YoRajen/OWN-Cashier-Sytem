<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseConnection;

/**
 * Class Products,
 * Class responsible for retrieving product and inventory data.
 */
class Products extends BaseModel
{
    /** @var array|null Cached product rows, including their category name. */
    private ?array $productData = null;
    /** @var int Timestamp when the cached product rows were loaded. */
    private int $lastUpdatedProductData = 0;
    /** @var int Maximum age of cached product data, in seconds. */
    private static int $MAX_LAST_UPDATED_PRODUCT_DATA = 5;

    /**
     * Initializes the Products model with an optional database connection.
     * @param DatabaseConnection|null $dbConnection Optional database connection.
     */
    public function __construct(?DatabaseConnection $dbConnection = null)
    {
        $this->dbConnection = $dbConnection ?? DatabaseConnection::getConnectionInstance();
    }

    /**
     * Loads all products and their category names, reusing the cached rows while valid.
     * @return array Product rows, or an empty array when no products exist.
     */
    public function loadAllProducts(): array
    {
        if ($this->isProductDataUpToDate()) {
            return $this->productData ?? [];
        }

        $this->dbConnection->query(
            'SELECT p.product_id, p.product_name, p.product_price, p.product_stock, p.category_id, c.category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.category_id
             ORDER BY p.category_id ASC, p.product_id ASC'
        );
        $this->productData = $this->dbConnection->getFetchResults();
        $this->lastUpdatedProductData = time();

        return $this->productData;
    }

    /**
     * Retrieves a product by its ID.
     * @param string $id Product ID (the database uses a string product code).
     * @return array|null Product row, or null when it does not exist.
     */
    public function getProductById(string $id): ?array
    {
        foreach ($this->getCachedProducts() as $product) {
            if ((string) $product['product_id'] === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Retrieves products assigned to a category.
     * @param int $categoryId Category ID.
     * @return array Matching product rows.
     */
    public function getProductsByCategoryId(int $categoryId): array
    {
        return array_values(array_filter(
            $this->getCachedProducts(),
            static fn(array $product): bool => (int) $product['category_id'] === $categoryId
        ));
    }

    /**
     * Retrieves products whose name exactly matches the provided name.
     * @param string $name Product name.
     * @return array|null Product row, or null when no product matches.
     */
    public function getProductByName(string $name): ?array
    {
        foreach ($this->getCachedProducts() as $product) {
            if ($product['product_name'] === $name) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Returns cached product rows, refreshing the cache when it has expired.
     * @return array Product rows.
     */
    public function getCachedProducts(): array
    {
        return $this->isProductDataUpToDate()
            ? ($this->productData ?? [])
            : $this->loadAllProducts();
    }

    /**
     * Checks whether the cached product data is still within its time limit.
     * @return bool True when cached product data is still valid.
     */
    private function isProductDataUpToDate(): bool
    {
        return $this->productData !== null
            && (time() - $this->lastUpdatedProductData) < self::$MAX_LAST_UPDATED_PRODUCT_DATA;
    }
}