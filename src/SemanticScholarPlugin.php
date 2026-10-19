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

    /**
     * Translation catalogues this plugin ships.
     *
     * Each directory holds one `<lang>.json` per language. Spora resolves
     * a string through **your** catalogue first, then the host's, then the
     * English literal itself, so shipping a catalogue is purely additive:
     * nothing breaks while `de.json` is missing or partial.
     *
     * Keys are the English source text — `de.json` reads
     * `{"Semantic Scholar": "Semantic-Scholar"}`, not
     * `{"tool": "Semantic-Scholar"}`. That is what lets the host's CI fail
     * when a string is edited without being re-translated: the key changes,
     * the old entry orphans.
     *
     * **Not everything English belongs in here.** A string the model reads
     * is never translated — not `#[Tool].description`, not
     * `#[ToolParameter].description`, not `#[ToolSetting].label` or its
     * `options`, and not `ToolResult` content. What `locales/de.json`
     * holds is every `#[ToolSetting].description` plus the tool's
     * `displayName`, each a string only an operator ever reads.
     *
     * Requires `spora-core >= 0.31.0` at runtime; older versions fail to
     * resolve the method when the loader is strict.
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
