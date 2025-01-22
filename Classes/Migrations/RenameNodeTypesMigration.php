<?php

namespace Carbon\AutoMigrate\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename Node Types
 *
 * Create a PHP file under Migrations/Mysql
 *
 * <?php
 * namespace Neos\Flow\Persistence\Doctrine\Migrations;
 * use Carbon\AutoMigrate\Migrations\RenameNodeTypesMigration;
 * class VersionYYYYMMDDHHMMSS extends RenameNodeTypesMigration
 * {
 *      public array $nodeTypes = [
 *          'Vendor.Example:Content.OldNodeType' => 'Vendor.Example:Content.NewNodeType',
 *          'Vendor.Example:Document.OldNodeType' => 'Vendor.Example:Document.NewNodeType',
 *      ];
 * }
 */
class RenameNodeTypesMigration extends AbstractMigration
{
    public array $nodeTypes = [];

    public function up(Schema $schema): void
    {
        $this->rename(true);
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->rename(false);
    }

    /**
     * Execute the migration
     *
     * @param boolean $up
     * @return void
     */
    private function rename(bool $up): void
    {
        foreach ($this->nodeTypes as $old => $new) {
            $oldNodeType = $up ? $old : $new;
            $newNodeType = $up ? $new : $old;

            $this->addSql(
                sprintf(
                    "UPDATE neos_neos_domain_model_site SET siteresourcespackagekey = REPLACE(siteresourcespackagekey, '%s', '%s')",
                    $oldNodeType,
                    $newNodeType
                )
            );

            $this->addSql(
                sprintf(
                    "UPDATE neos_contentrepository_domain_model_nodedata SET nodetype = REPLACE(nodetype, '%s', '%s')",
                    $oldNodeType,
                    $newNodeType
                )
            );
        }
    }
}
