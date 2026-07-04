# Semantic Scholar Plugin for Spora

Academic paper search and metadata retrieval — backed by the public
[Semantic Scholar](https://www.semanticscholar.org) Graph API (Allen Institute
for AI). Free, no API key required; an optional key raises the rate limit.

## Installation

```bash
# Recommended — install via the Spora CLI
php bin/spora plugin:install spora-ai/spora-plugin-semantic-scholar
php bin/spora spora:install   # applies the plugin's migration

# For development against a sibling git clone, pass --path:
php bin/spora plugin:install spora-ai/spora-plugin-semantic-scholar --path=/abs/path/to/checkout

# Alternative — drop a clone into the Spora repo
git clone https://github.com/spora-ai/spora-plugin-semantic-scholar.git plugins/semantic-scholar
php bin/spora spora:install

# Alternative — external path (no Spora checkout changes)
git clone https://github.com/spora-ai/spora-plugin-semantic-scholar.git /opt/spora-plugins/semantic-scholar
echo 'SPORA_PLUGINS_PATHS=/opt/spora-plugins/semantic-scholar' >> .env
php bin/spora spora:install
```

After install, the tool is exposed as `semantic_scholar` with five
operations:

- `semantic-scholar:paper_search` — keyword search
- `semantic-scholar:get_paper` — full metadata for one paper
- `semantic-scholar:get_citations` — papers that cite a given paper
- `semantic-scholar:get_references` — papers referenced by a given paper
- `semantic-scholar:get_recommendations` — related papers for a given paper

## Configuration

Settings → Tools → Semantic Scholar.

The plugin talks to the public Semantic Scholar Graph API
(`https://api.semanticscholar.org`). The base URL is currently fixed and
cannot be overridden from the UI.

| Setting | Required | Default | Notes |
|---|---|---|---|
| `core.semantic_scholar.http_timeout` | no | `30` | Per-request timeout in seconds. Can also be set with the `SPORA_TOOL_HTTP_TIMEOUT` env var. |
| API key | no | — | Optional. Sign up at <https://www.semanticscholar.org/product/api> to receive a private key. The free tier works without one; a key raises the per-IP rate limit. |

**Rate limits** (vendor-published, see
[API product page](https://www.semanticscholar.org/product/api)):

- **Unauthenticated** — shared budget of 1000 RPS across all anonymous
  callers; further throttling under load.
- **Authenticated** — introductory 1 RPS, with higher limits available to
  approved keys.

Either way, a single API failure cannot kill the agent loop: every call
returns a `ToolResult::ok` or `ToolResult::fail` with a human-readable
message, never throws.

## Per-tool parameters

The tool accepts an `action` discriminator selecting one of the five
operations. All parameters are optional unless noted.

| Operation | Parameters | Returns |
|---|---|---|
| `paper_search` | `query` (string, **required**), `limit` (1-100, default 10), `year` (string, e.g. `2023` / `2020-2024` / `<2020`), `open_access_only` (boolean) | `total`, `returned`, `query`, plus formatted list of papers (title, authors, year, venue, citations, DOI, PDF link, abstract excerpt) |
| `get_paper` | `paper_id` (string, **required** — 40-char Semantic Scholar ID, or DOI / ArXiv / PubMed) | `paper_id`, `title`, plus full formatted metadata including DOI, ArXiv, PubMed, open-access PDF |
| `get_citations` | `paper_id` (string, **required**), `limit` (1-100, default 20), `offset` (default 0) | `paper_id`, `total`, `returned`, `offset`, plus formatted citing papers |
| `get_references` | `paper_id` (string, **required**), `limit` (1-100, default 20), `offset` (default 0) | `paper_id`, `total`, `returned`, `offset`, plus formatted referenced papers |
| `get_recommendations` | `paper_id` (string, **required**), `limit` (1-20, default 10) | `paper_id`, `returned`, plus formatted recommended papers |

**Fields returned by the API** (set by the plugin, not configurable per
call): `title, abstract, authors, year, venue, citationCount, url,
openAccessPdf, externalIds, isOpenAccess`. Recommendations omits
`isOpenAccess`.

Example agent invocation:

```json
{
  "action": "paper_search",
  "query": "transformer attention is all you need",
  "limit": 5,
  "year": "2017-2024"
}
```

```json
{
  "action": "get_citations",
  "paper_id": "DOI:10.1038/nature14539",
  "limit": 10,
  "offset": 0
}
```

## Vendor links

- **API product page / signup** — <https://www.semanticscholar.org/product/api>
- **API reference** — <https://api.semanticscholar.org/api-docs/>
- **API License Agreement** — <https://www.semanticscholar.org/product/api/license>

## Development

```bash
composer install
./vendor/bin/pest           # tests
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

CI: `.github/workflows/ci.yml` — Pest on PHP 8.4 + 8.5, PHPStan, php-cs-fixer
dry-run, plus SonarCloud analysis (project key
`spora-ai_spora-plugin-semantic-scholar`). MIT license.
