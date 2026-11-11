<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DatabaseConnection;

/**
 * Class Categories,
 * Class responsible for retrieving product category data.
 */
class Categories extends BaseModel
{
    /** @var array|null Cached category rows. */
    private ?array $categoryData = null;
    /** @var int Timestamp when the cached category rows were loaded. */
    private int $lastUpdatedCategoryData = 0;
    /** @var int Maximum age of cached category data, in seconds. */
    private static int $MAX_LAST_UPDATED_CATEGORY_DATA = 5;

    /**
     * Initializes the Categories model with an optional database connection.
     * @param DatabaseConnection|null $dbConnection Optional database connection.
     */
    public function __construct(?DatabaseConnection $dbConnection = null)
    {
        $this->dbConnection = $dbConnection ?? DatabaseConnection::getConnectionInstance();
    }

    /**
     * Loads all categories, reusing the cached rows while valid.
     * @return array Category rows, or an empty array when no categories exist.
     */
    public function loadAllCategories(): array
    {
        if ($this->isCategoryDataUpToDate()) {
            return $this->categoryData ?? [];
        }

        $this->dbConnection->query(
            'SELECT category_id, category_code, category_name
             FROM categories
             ORDER BY category_id ASC'
        );
        $this->categoryData = $this->dbConnection->getFetchResults();
        $this->lastUpdatedCategoryData = time();

        return $this->categoryData;
    }

    /**
     * Retrieves a category by its ID.
     * @param int $id Category ID.
     * @return array|null Category row, or null when it does not exist.
     */
    public function getCategoryById(int $id): ?array
    {
        foreach ($this->getCachedCategories() as $category) {
            if ((int) $category['category_id'] === $id) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Retrieves a category by its unique code.
     * @param string $code Category code.
     * @return array|null Category row, or null when it does not exist.
     */
    public function getCategoryByCode(string $code): ?array
    {
        foreach ($this->getCachedCategories() as $category) {
            if ($category['category_code'] === $code) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Retrieves a category by its exact name.
     * @param string $name Category name.
     * @return array|null Category row, or null when it does not exist.
     */
    public function getCategoryByName(string $name): ?array
    {
        foreach ($this->getCachedCategories() as $category) {
            if ($category['category_name'] === $name) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Returns cached category rows, refreshing the cache when it has expired.
     * @return array Category rows.
     */
    public function getCachedCategories(): array
    {
        return $this->isCategoryDataUpToDate()
            ? ($this->categoryData ?? [])
            : $this->loadAllCategories();
    }

    /**
     * Checks whether the cached category data is still within its time limit.
     * @return bool True when cached category data is still valid.
     */
    private function isCategoryDataUpToDate(): bool
    {
        return $this->categoryData !== null
            && (time() - $this->lastUpdatedCategoryData) < self::$MAX_LAST_UPDATED_CATEGORY_DATA;
    }
}