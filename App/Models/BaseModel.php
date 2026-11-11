<?php

declare(strict_types=1);

namespace App\Models;
use App\Services\DatabaseConnection;


/**
 * Class BaseModel,
 * Abstract base class for all models in the application.
 * Provides common functionality for managing attributes, relations, and database connections.
 * @package App\Models
 */
abstract class BaseModel
{
    /** @var DatabaseConnection $dbConnection The database connection instance. */
    protected ?DatabaseConnection $dbConnection = null;
    /** @var array $attributes An associative array to hold model attributes. */
    protected array $attributes = [];
    /** @var array $relations An associative array to hold model relations. */
    protected array $relations = [];

    /**
     * sets the database connection for the model.
     * @param DatabaseConnection $dbConnection The database connection instance to be set.
     */
    public function setConnection(DatabaseConnection $dbConnection): void
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * serializes the model's attributes and relations into a array representation.
     * @return array The serialized array representation of the model.
     */
    public function __serialize(): array
    {
        return [
            'attributes' => $this->attributes,
            'relations' => $this->relations,
        ];
    }

    /**
     * unserializes the provided string representation into the model's attributes and relations.
     * @param array $data The array containing serialized data for attributes and relations.
     */
    public function __unserialize(array $data): void
    {
        $this->attributes = $data['attributes'] ?? [];
        $this->relations = $data['relations'] ?? [];
        $this->dbConnection = null;
    }

    /**
     * Magic method to get the value of a property.
     * @param string $name The name of the property to retrieve.
     * @return mixed The value of the property, or null if not found.
     */
    public function __get(string $name) : mixed
    {

        if (isset($this->relations[$name])) {
            return $this->relations[$name];
        }

        if (method_exists($this, $name)) {
            $this->relations[$name] = $this->$name();
            return $this->relations[$name];
        }

        return $this->attributes[$name] ?? null;
    }
    
    /**
     * Magic method to set the value of a property.
     * @param string $name The name of the property to set.
     * @param mixed $value The value to assign to the property.
     */
    public function __set(string $name, mixed $value) : void
    {
        if ($value instanceof BaseModel) {
            $this->relations[$name] = $value;
            return;
        }

        $this->attributes[$name] = $value;
    }
}