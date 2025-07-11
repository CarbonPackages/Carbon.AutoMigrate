<?php
namespace Carbon\AutoMigrate\Migrations;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

/**
 * Change the numeric value of a given property.
 *
 * `property` The name of the property to change.
 * `type` The type of the calulation (addition, substract, multiply, divide, +, -, x, * , :, /, ). If not set it will set the property by `value`.
 * `value` The value to make the calculation with or set the property to
 * `defaultValue` The default value to use if the property is not set.
 * `max` The maximal value to use for the property.
 * `min` The minimal value to use for the property.
 *
 */
class ChangeNumericPropertyValueMigration extends AbstractTransformation
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var float
     */
    protected $value;

    /**
     * @var float
     */
    protected $defaultValue;

    /**
     * @var float
     */
    protected $min;

    /**
     * @var float
     */
    protected $max;


    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setValue($value)
    {
        $this->value = (float) $value;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = (float) $defaultValue;
    }

    public function setMin($min)
    {
        $this->min = (float) $min;
    }

    public function setMax($max)
    {
        $this->max = (float) $max;
    }

    /**
     * If the given node has the property this transformation should work on, this
     * returns true.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return ($node->hasProperty($this->property));
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function execute(NodeData $node)
    {
        $currentPropertyValue = $node->getProperty($this->property);

        if (!$this->value || !is_numeric($this->value) || !is_numeric($currentPropertyValue)) {
            if (is_numeric($this->defaultValue)) {
                $currentPropertyValue = (float) $this->defaultValue;
                $node->setProperty($this->property, $currentPropertyValue);
            }
            return;
        }

        $currentPropertyValue = (float) $currentPropertyValue;

        switch ($this->type) {
            case 'addition':
            case '+':
                $currentPropertyValue += $this->value;
                break;
            case 'subtract':
            case '-':
                $currentPropertyValue -= $this->value;
                break;
            case 'multiply':
            case '*':
            case 'x':
                $currentPropertyValue *= $this->value;
                break;
            case 'divide':
            case ':':
            case '/':
                if ($this->value === 0) {
                    throw new \Exception("Division by zero is not allowed", 1752271677);
                }
                $currentPropertyValue /= $this->value;
                break;
            default:
                // If no type is set, we just set the property to the value
                $currentPropertyValue = $this->value;
                break;
        }

        if (isset($this->min) && $currentPropertyValue < $this->min) {
            $currentPropertyValue = $this->min;
        }
        if (isset($this->max) && $currentPropertyValue > $this->max) {
            $currentPropertyValue = $this->max;
        }

        $node->setProperty($this->property, $currentPropertyValue);
    }
}
