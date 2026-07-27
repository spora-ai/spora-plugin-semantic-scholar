<?php

declare(strict_types=1);

use Spora\Plugins\SemanticScholar\Tools\SemanticScholarTool;
use Spora\Tools\Attributes\ToolParameter;

/**
 * Per-op `required[]` binding tests for SemanticScholarTool.
 *
 * Reads `#[ToolParameter]` constructor arguments via reflection.
 * Independent of the bound spora-core version — once spora-core ships
 * the `bool|array $required` signature AND the plugin bumps its dep,
 * replace with `ToolParameterSchemaBuilder::build(SemanticScholarTool::class)`.
 */
function scholarToolParameterArgs(string $name): array
{
    $reflection = new ReflectionClass(SemanticScholarTool::class);
    foreach ($reflection->getAttributes(ToolParameter::class) as $attribute) {
        $args = $attribute->getArguments();
        if (($args['name'] ?? null) === $name) {
            return $args;
        }
    }

    throw new RuntimeException("ToolParameter '{$name}' not declared on " . SemanticScholarTool::class);
}

it('binds query to paper_search only', function () {
    expect(scholarToolParameterArgs('query')['required'])->toBe(['paper_search']);
});

it('binds paper_id to the 4 get_* ops', function () {
    $expected = ['get_paper', 'get_citations', 'get_references', 'get_recommendations'];
    $actual = scholarToolParameterArgs('paper_id')['required'];
    sort($expected);
    sort($actual);
    expect($actual)->toBe($expected);
});

it('keeps limit, offset, year, open_access_only at required: false', function () {
    expect(scholarToolParameterArgs('limit')['required'])->toBeFalse();
    expect(scholarToolParameterArgs('offset')['required'])->toBeFalse();
    expect(scholarToolParameterArgs('year')['required'])->toBeFalse();
    expect(scholarToolParameterArgs('open_access_only')['required'])->toBeFalse();
});