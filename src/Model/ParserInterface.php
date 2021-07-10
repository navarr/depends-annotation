<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Model;

use JetBrains\PhpStorm\ArrayShape;
use Navarr\Depends\Data\DeclaredDependency;

interface ParserInterface
{
    /**
     * @return DeclaredDependency[]
     */
    #[ArrayShape([DeclaredDependency::class])]
    public function parse(string $contents): array;

    public function setIssueHandler(IssueHandlerInterface $handler): void;
}
