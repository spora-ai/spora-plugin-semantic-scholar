<?php

declare(strict_types=1);

namespace Spora\Plugins\SemanticScholar;

use Spora\Plugins\AbstractPlugin;
use Spora\Plugins\SemanticScholar\Tools\SemanticScholarTool;

/**
 * Plugin entry point for the SemanticScholar extraction.
 *
 * Semantic Scholar academic paper search for Spora agents.
 */
final class SemanticScholarPlugin extends AbstractPlugin
{
    public function getName(): string
    {
        return 'SemanticScholar';
    }

    /** @return array<class-string<\Spora\Tools\ToolInterface>> */
    public function tools(): array
    {
        return [
            SemanticScholarTool::class,
        ];
    }
}
