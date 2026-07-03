<?php

declare(strict_types=1);

namespace Spora\Plugins\SemanticScholar;

use Spora\Plugins\AbstractPlugin;

/**
 * Placeholder plugin entry point for the SemanticScholar extraction (v0.1.0).
 *
 * The real tool class lands in a follow-up release. This file declares the
 * plugin and an empty hook surface so the framework can install, boot, and
 * inspect it before any tools are available.
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
        return [];
    }
}
