<?php

declare(strict_types=1);

namespace Spora\Plugins\SemanticScholar;

use Spora\Plugins\AbstractPlugin;
use Spora\Plugins\SemanticScholar\Tools\SemanticScholarTool;

/**
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

    /**
     * The plugin's `locales/` directory — one `<lang>.json` per language.
     *
     * @return string[]
     */
    public function translationsPaths(): array
    {
        return [
            __DIR__ . '/../locales',
        ];
    }
}
