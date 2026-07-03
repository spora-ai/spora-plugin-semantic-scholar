<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Spora\Plugins\SemanticScholar\Tools\SemanticScholarTool;
use Spora\Services\ToolConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

const SCHOLAR_PAPER_ID_REQUIRED = 'paper_id parameter is required';
const SCHOLAR_TRANSFORMER_TITLE = 'Attention is All You Need';
const SCHOLAR_TRANSFORMER_AUTHOR = 'Ashish Vaswani';
const SCHOLAR_TRANSFORMER_DOI = '10.48550/arXiv.1706.03762';

/**
 * Build a fresh SemanticScholarTool with real config + http-client mocks.
 * Returns [$config, $client, $tool]. PHPStan-friendly: every test gets a
 * locally-typed $tool without relying on Pest's dynamic $this->.
 *
 * @return array{0: ToolConfigService&Mockery\MockInterface, 1: HttpClientInterface&Mockery\MockInterface, 2: SemanticScholarTool}
 */
function makeScholarTool(): array
{
    $config = Mockery::mock(ToolConfigService::class);
    $client = Mockery::mock(HttpClientInterface::class);
    return [$config, $client, new SemanticScholarTool($config, $client)];
}

it('returns error if query is empty on paper_search', function () {
    [, , $tool] = makeScholarTool();

    $result = $tool->execute(['action' => 'paper_search', 'query' => ''], 1);
    expect($result->success)->toBeFalse()
        ->and($result->content)->toContain('query cannot be empty');
});

it('returns error if paper_id is missing', function (string $action) {
    [, , $tool] = makeScholarTool();

    $result = $tool->execute(['action' => $action, 'paper_id' => ''], 1);
    expect($result->success)->toBeFalse()
        ->and($result->content)->toContain(SCHOLAR_PAPER_ID_REQUIRED);
})->with(['get_paper', 'get_citations', 'get_references', 'get_recommendations']);

it('returns error for unknown action', function () {
    [, , $tool] = makeScholarTool();

    $result = $tool->execute(['action' => 'unknown_action'], 1);
    expect($result->success)->toBeFalse()
        ->and($result->content)->toContain("Unknown action 'unknown_action'");
});

it('paper_search makes correct HTTP request and parses response', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn([
        'total' => 2,
        'data' => [
            [
                'title' => SCHOLAR_TRANSFORMER_TITLE,
                'abstract' => 'We propose a new simple network architecture.',
                'authors' => [['name' => SCHOLAR_TRANSFORMER_AUTHOR, 'authorId' => 'a1']],
                'year' => 2017,
                'venue' => 'NeurIPS',
                'citationCount' => 98523,
                'url' => 'https://arxiv.org/abs/1706.03762',
                'externalIds' => ['DOI' => SCHOLAR_TRANSFORMER_DOI, 'ArXiv' => '1706.03762'],
                'openAccessPdf' => ['url' => 'https://arxiv.org/pdf/1706.03762'],
            ],
            [
                'title' => 'BERT: Pre-training of Deep Bidirectional Transformers',
                'abstract' => 'We introduce a new language understanding model.',
                'authors' => [['name' => 'Jacob Devlin', 'authorId' => 'j1']],
                'year' => 2018,
                'venue' => 'NAACL',
                'citationCount' => 72341,
                'url' => 'https://arxiv.org/abs/1810.04805',
                'externalIds' => ['DOI' => '10.48550/arXiv.1810.04805'],
                'openAccessPdf' => null,
            ],
        ],
    ]);

    $client->expects('request')->with('GET', 'https://api.semanticscholar.org/graph/v1/paper/search', Mockery::on(function ($options) {
        return $options['query']['query'] === 'transformer attention'
            && $options['query']['limit'] === 10
            && str_contains($options['query']['fields'], 'title');
    }))->andReturn($response);

    $result = $tool->execute(['action' => 'paper_search', 'query' => 'transformer attention'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('PAPER SEARCH RESULTS')
        ->and($result->content)->toContain(SCHOLAR_TRANSFORMER_TITLE)
        ->and($result->content)->toContain(SCHOLAR_TRANSFORMER_AUTHOR)
        ->and($result->content)->toContain('2017')
        ->and($result->content)->toContain('NeurIPS')
        ->and($result->content)->toContain('98523')
        ->and($result->content)->toContain(SCHOLAR_TRANSFORMER_DOI);
});

it('paper_search returns empty message when no results', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn(['total' => 0, 'data' => []]);

    $client->expects('request')->andReturn($response);

    $result = $tool->execute(['action' => 'paper_search', 'query' => 'xyznonexistent'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('No papers found');
});

it('get_paper fetches paper metadata by ID', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn([
        'title' => SCHOLAR_TRANSFORMER_TITLE,
        'abstract' => 'We propose a new simple network architecture.',
        'authors' => [['name' => SCHOLAR_TRANSFORMER_AUTHOR, 'authorId' => 'a1']],
        'year' => 2017,
        'venue' => 'NeurIPS',
        'citationCount' => 98523,
        'url' => 'https://arxiv.org/abs/1706.03762',
        'externalIds' => ['DOI' => SCHOLAR_TRANSFORMER_DOI, 'ArXiv' => '1706.03762'],
        'openAccessPdf' => ['url' => 'https://arxiv.org/pdf/1706.03762'],
    ]);

    $client->expects('request')->with('GET', Mockery::type('string'), Mockery::on(function ($options) {
        return str_contains($options['query']['fields'], 'title');
    }))->andReturn($response);

    $result = $tool->execute(['action' => 'get_paper', 'paper_id' => 'ArXiv:1706.03762'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('PAPER DETAILS')
        ->and($result->content)->toContain(SCHOLAR_TRANSFORMER_TITLE);
});

it('get_citations returns citing papers with pagination', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn([
        'total' => 1,
        'data' => [
            ['citingPaper' => [
                'title' => 'Improving language understanding by generative pre-training',
                'authors' => [['name' => 'Alec Radford', 'authorId' => 'r1']],
                'year' => 2018,
                'venue' => '',
                'citationCount' => 45678,
                'url' => 'https://openai.com/research/gpt',
                'externalIds' => ['DOI' => '10.1000/xyz'],
                'openAccessPdf' => null,
            ]],
        ],
    ]);

    $client->expects('request')->with('GET', Mockery::type('string'), Mockery::on(function ($options) {
        return $options['query']['limit'] === 20
            && $options['query']['offset'] === 0;
    }))->andReturn($response);

    $result = $tool->execute(['action' => 'get_citations', 'paper_id' => 'abc123'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('CITATIONS OF')
        ->and($result->content)->toContain('Alec Radford');
});

it('get_references returns referenced papers', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn([
        'total' => 1,
        'data' => [
            ['citedPaper' => [
                'title' => 'Sequence to sequence learning with neural networks',
                'authors' => [['name' => 'Ilya Sutskever', 'authorId' => 's1']],
                'year' => 2014,
                'venue' => 'NeurIPS',
                'citationCount' => 23456,
                'url' => 'https://papers.nips.cc/paper/5346',
                'externalIds' => [],
                'openAccessPdf' => null,
            ]],
        ],
    ]);

    $client->expects('request')->andReturn($response);

    $result = $tool->execute(['action' => 'get_references', 'paper_id' => 'abc123'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('REFERENCES OF')
        ->and($result->content)->toContain('Ilya Sutskever');
});

it('get_recommendations returns recommended papers', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn([
        'recommendedPapers' => [
            [
                'title' => 'Deep learning for natural language processing',
                'authors' => [['name' => 'Yoav Goldberg', 'authorId' => 'g1']],
                'year' => 2017,
                'venue' => 'Morgan & Claypool',
                'citationCount' => 3456,
                'url' => 'https://www.morganclaypool.com/doi/10.2200/S00762ED1V01Y201703HLT037',
                'externalIds' => ['DOI' => '10.2200/S00762ED1V01Y201703HLT037'],
                'openAccessPdf' => null,
            ],
        ],
    ]);

    $client->expects('request')->andReturn($response);

    $result = $tool->execute(['action' => 'get_recommendations', 'paper_id' => 'abc123'], 1);
    expect($result->success)->toBeTrue()
        ->and($result->content)->toContain('RECOMMENDED PAPERS')
        ->and($result->content)->toContain('Yoav Goldberg');
});

it('handles HTTP error codes gracefully', function () {
    [$config, $client] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(429);
    $response->allows('getContent')->andReturn('Rate limit exceeded');

    $client->expects('request')->andReturn($response);
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->allows('error');
    $logger->allows('debug');

    $tool = new SemanticScholarTool($config, $client, $logger);

    $result = $tool->execute(['action' => 'paper_search', 'query' => 'test'], 1);
    expect($result->success)->toBeFalse()
        ->and($result->content)->toContain('HTTP 429');
});

it('handles HTTP timeout from settings', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([
        'core.semantic_scholar.http_timeout' => '60',
    ]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn(['total' => 0, 'data' => []]);

    $client->expects('request')->with('GET', Mockery::any(), Mockery::on(function ($options) {
        return $options['timeout'] === 60;
    }))->andReturn($response);

    $result = $tool->execute(['action' => 'paper_search', 'query' => 'test'], 1);
    expect($result->success)->toBeTrue();
});

it('paper_search respects open_access_only and year filters', function () {
    [$config, $client, $tool] = makeScholarTool();
    $config->allows('getEffectiveSettings')->andReturn([]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('getStatusCode')->andReturn(200);
    $response->allows('toArray')->andReturn(['total' => 0, 'data' => []]);

    $client->expects('request')->with('GET', Mockery::any(), Mockery::on(function ($options) {
        return $options['query']['year'] === '2023'
            && $options['query']['openAccessPdf'] === 'true';
    }))->andReturn($response);

    $result = $tool->execute([
        'action' => 'paper_search',
        'query' => 'test',
        'year' => '2023',
        'open_access_only' => true,
    ], 1);
    expect($result->success)->toBeTrue();
});
