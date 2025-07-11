# Carbon.AutoMigrate

Sometimes, things change. This package helps you to migrate old installation to up-to-date installations. This help
to run migrations after deployments.

## Installation

This package is available via [packagist]. Run `composer require carbon/automigrate --no-update` in your
site package. After that, run `composer update` in your root directory.

## How to use it

Add your node migrations version numbers to your `Settings.yaml`:

```yaml
Carbon:
  AutoMigrate:
    node:
      20241005070000: true
      20241005090000: true
```

Configure your stack to run `./flow node:automigrate` after `./flow doctrine:migrate`. The command will check if the
migrations are available, checks if the migrations has already been applied, and if not, the migrations get's applied.

### Options

Run `./flow help node:automigrate` to see the options:

```bash
--confirmation       Confirm application of this migration, only needed if the given migration contains any warnings.
--dry-run            If true, no changes will be made
```

## Aditional migration helper

### [ChangeNumericPropertyValueMigration](Classes/Migrations/ChangeNumericPropertyValueMigration.php)

Change the numeric value of a given property.

A migration could look like this:

```yaml
up:
  comments: 'Adjust font sizes'
  migration:
    - filters:
        - type: 'NodeType'
          settings:
            nodeType: 'Litefyr.Integration:Content.Headline'
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\ChangeNumericPropertyValueMigration'
          settings:
            property: 'fontSize'
            type: '+'
            value: 4
            defaultValue: 6
            max: 10

down:
  comments: "Revert adjust font sizes"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: 'Litefyr.Integration:Content.Headline'
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\ChangeNumericPropertyValueMigration'
          settings:
            property: 'fontSize'
            type: '-'
            value: 4
            defaultValue: 6
            min: 1
```

### [ChangePropertyValueMigration](Classes/Migrations/ChangePropertyValueMigration.php)

This is basically the same as the original ChangePropertyValue transformation from Neos.ContentRepository but with the
added ability to search and replace in numeric values. * This is useful when you want to change a property value that
is a number but you want to change it to a string.

### [RenamePropertyValuesMigration](Classes/Migrations/RenamePropertyValuesMigration.php)

This can be used to rename one or multiply property values. This is also possible with default yaml, but need many
lines, if you have multiple values to change.

A migration could look like this:

```yaml
up:
  comments: "Switch property values"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: "Foo.Bar:Mixin.Direction"
            withSubTypes: true
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\RenamePropertyValuesMigration'
          settings:
            propertyName: direction
            values:
              oldValue: newValue
              northEast: topRight
              northWest: topLeft
              southEast: bottomRight
              southWest: bottomLeft

down:
  comments: "Revert property value switch"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: "Foo.Bar:Mixin.Direction"
            withSubTypes: true
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\RenamePropertyValuesMigration'
          settings:
            propertyName: direction
            values:
              newValue: oldValue
              topRight: northEast
              topLeft: northWest
              bottomRight: southEast
              bottomLeft: southWest
```

### [RenameNodeTypesMigration](Classes/Migrations/RenameNodeTypesMigration.php)

This can be used if you want to rename NodeTypes. This not only change the `nodetype` in the table
`neos_contentrepository_domain_model_nodedata`, it also set the `siteresourcespackagekey` in
`neos_neos_domain_model_site` if the node type match.

You have to create a PHP file in your package under `Migrations/Mysql`

```php
<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Carbon\AutoMigrate\Migrations\RenameNodeTypesMigration;

class Version20241005130500 extends RenameNodeTypesMigration
{
  public array $nodeTypes = [
    "Vendor.Example:Content.OldNodeType" => "Vendor.Example:Content.NewNodeType",
    "Vendor.Example:Document.OldNodeType" => "Vendor.Example:Document.NewNodeType",
  ];
}
```

The migration will automatically run if you run `./flow doctrine:migrations`.

[packagist]: https://packagist.org/packages/carbon/automigrate
