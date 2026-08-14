<?php

namespace Carbon\AutoMigrate\Migrations\Filters;

use Neos\ContentRepository\Migration\Filters\DoctrineFilterInterface;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\Exception\InvalidQueryException;

/**
 * Filter nodes having the given property and a matching value. Can be also inverted.
 */
class PropertyValue implements DoctrineFilterInterface
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $propertyValue;

    /**
     * @var bool
     */
    protected $inverted = false;

    /**
     * Set the inverted flag to true or false to invert the filter or not.
     *
     * @param bool $inverted
     * @return void
     */
    public function setInverted(bool $inverted): void
    {
        $this->inverted = $inverted ?? false;
    }

    /**
     * Sets the property name to be checked.
     *
     * @param string $propertyName
     * @return void
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Sets the property value to be checked against.
     *
     * @param string|bool|int $propertyValue
     * @return void
     */
    public function setPropertyValue($propertyValue): void
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * Filters for nodes having the property and value requested.
     *
     * @param Query $baseQuery
     * @return array
     * @throws InvalidQueryException
     */
    public function getFilterExpressions(Query $baseQuery): array
    {
        // Build the like parameter as "key": "value" to search by a specific key and value
        // See NodeDataRepository.findByProperties() for the "inspiration"
        $likeParameter = sprintf("%%%s%%", trim(json_encode(
            [$this->propertyName => $this->propertyValue],
            JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
        ), "{}\n\t "));

        $likeExpression = $baseQuery->like('properties', $likeParameter, false);

        return [$this->inverted ? $baseQuery->logicalNot($likeExpression) : $likeExpression];
}
