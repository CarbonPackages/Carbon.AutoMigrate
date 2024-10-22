<?php

namespace Carbon\AutoMigrate\Migrations;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Neos\Controller\CreateContentContextTrait;

class RenamePropertyValuesMigration extends AbstractTransformation
{
    use CreateContentContextTrait;

    public string $propertyName = '';
    public array $values = [];

    /**
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        if (!$node->hasProperty($this->propertyName) || !$node->getProperty($this->propertyName)) {
            return false;
        }
        return true;
    }

    public function execute(NodeData $node)
    {
        $oldValue = $node->getProperty($this->propertyName);
        $newValue = $this->values[$oldValue] ?? $oldValue;
        $node->setProperty($this->propertyName, $newValue);
    }
}
