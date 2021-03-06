<?php
/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

use Navarr\Attribute\Dependency;

#[Dependency('example', '^1', 'Class Attribute')]
#[Dependency('example')]
#[Dependency('example', '^8')]
class TestClassA
{
    #[Dependency('example', '^2', 'Constant Attribute')]
    const EXAMPLE = 5;

    #[Dependency('example', '^3', 'Property Attribute')]
    private $property;

    #[Dependency('example', '^4', 'Method Attribute')]
    #[Dependency(versionConstraint: '^9', reason: 'Mixed Parameter Order', package: 'example')]
    public function exampleMethodB(
        #[Dependency('example', '^5', 'Method Parameter Attribute')] $parameter
    ) {
        return;
    }
}

#[Dependency('example', '^6', 'Function Attribute')]
function exampleFunction(
    #[Dependency('example', '^7', 'Function Parameter Attribute')] $parameter
) {
    return;
}
