<?php

declare(strict_types=1);

use Spora\Plugins\SemanticScholar\SemanticScholarPlugin;
use Spora\Plugins\SemanticScholar\Tools\SemanticScholarTool;

it('returns plugin name', function () {
    $plugin = new SemanticScholarPlugin();
    expect($plugin->getName())->toBe('SemanticScholar');
});

it('contributes the SemanticScholarTool', function () {
    $plugin = new SemanticScholarPlugin();
    expect($plugin->tools())->toBe([SemanticScholarTool::class]);
});
