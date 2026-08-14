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

## Aditional filter migration helper

Filters define which nodes are affected by a migration. They are evaluated before the transformation is executed and
work like a condition: only matching nodes are processed.

The most common filters are the built-in ones from the Neos content repository, for example `NodeType` to restrict a
migration to one node type.

### [PropertyValue](Classes/Migrations/Filters/PropertyValue.php)

In addition, this package provides the custom `PropertyValue` filter to target nodes by a
specific property and value which can be inverted.

```yaml
up:
  comments: "Apply styling only to a specific content type"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: "Vendor.Site:Content.Headline"
            withSubTypes: true
        - type: "Carbon\AutoMigrate\Migrations\Filters\PropertyValue"
          settings:
            propertyName: "layout"
            propertyValue: "hero"
            inverted: true
      transformations:
       ...
```

`PropertyValue` searches in the node's serialized `properties` field for a matching key/value pair. This is useful when
you want to limit a migration to nodes that already contain a certain value. Setting `inverted: true` reverses the
condition so that only nodes without that property value are processed.

## Aditional transformation migration helper

### [ChangeNumericPropertyValue](Classes/Migrations/Transformations/ChangeNumericPropertyValue.php)

Change the numeric value of a given property.

A migration could look like this:

```yaml
up:
  comments: "Adjust font sizes"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: "Litefyr.Integration:Content.Headline"
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\Transformations\ChangeNumericPropertyValue'
          settings:
            property: "fontSize"
            type: "+"
            value: 4
            defaultValue: 6
            max: 10

down:
  comments: "Revert adjust font sizes"
  migration:
    - filters:
        - type: "NodeType"
          settings:
            nodeType: "Litefyr.Integration:Content.Headline"
      transformations:
        - type: 'Carbon\AutoMigrate\Migrations\Transformations\ChangeNumericPropertyValue'
          settings:
            property: "fontSize"
            type: "-"
            value: 4
            defaultValue: 6
            min: 1
```

### [ChangePropertyValue](Classes/Migrations/Transformations/ChangePropertyValue.php)

This is basically the same as the original ChangePropertyValue transformation from Neos.ContentRepository but with the
added ability to search and replace in numeric values. * This is useful when you want to change a property value that
is a number but you want to change it to a string.

### [RenamePropertyValues](Classes/Migrations/Transformations/RenamePropertyValues.php)

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
        - type: 'Carbon\AutoMigrate\Migrations\Transformations\RenamePropertyValues'
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
        - type: 'Carbon\AutoMigrate\Migrations\Transformations\RenamePropertyValues'
          settings:
            propertyName: direction
            values:
              newValue: oldValue
              topRight: northEast
              topLeft: northWest
              bottomRight: southEast
              bottomLeft: southWest
```

### [RenameNodeTypes](Classes/Migrations/Transformations/RenameNodeTypes.php)

This can be used if you want to rename NodeTypes. This not only change the `nodetype` in the table
`neos_contentrepository_domain_model_nodedata`, it also set the `siteresourcespackagekey` in
`neos_neos_domain_model_site` if the node type match.

You have to create a PHP file in your package under `Migrations/Mysql`

```php
<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Carbon\AutoMigrate\Migrations\Transformations\RenameNodeTypes;

class Version20241005130500 extends RenameNodeTypes
{
  public array $nodeTypes = [
    "Vendor.Example:Content.OldNodeType" =>
      "Vendor.Example:Content.NewNodeType",
    "Vendor.Example:Document.OldNodeType" =>
      "Vendor.Example:Document.NewNodeType",
  ];
}
```

The migration will automatically run if you run `./flow doctrine:migrations`.

[packagist]: https://packagist.org/packages/carbon/automigrate
