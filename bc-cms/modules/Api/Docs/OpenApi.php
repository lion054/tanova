<?php

namespace Modules\Api\Docs;

use App\Http\Middleware\ApiVersion;

/** Turns the registry (Doc) into an OpenAPI 3.1 document, code samples and a Postman collection. */
class OpenApi
{
    private const STD_ERRORS = [
        401 => ['Unauthorized', [['missing_api_key', 'No key was sent.'], ['invalid_api_key', 'The key is wrong, revoked or not for this mode.']]],
        402 => ['Subscription required', [['subscription_required', 'Live keys need an active plan. Test keys do not.']]],
        403 => ['Forbidden', [['read_only_key', 'A publishable (pk_) key tried to change something.'], ['insufficient_scope', 'The key is not allowed this area.'], ['api_key_revoked', 'The key was revoked.'], ['api_key_expired', 'The key has expired.'], ['rate_limit_exceeded', 'The key used its request allowance for the year.']]],
        404 => ['Not found', [['not_found', 'It does not exist, or it belongs to someone else.']]],
        422 => ['Validation failed', [['validation_failed', 'The data sent is not valid. `fields` says which.']]],
        429 => ['Too many requests', [['too_many_requests', 'More than 120 requests a minute with this key. Wait for `Retry-After` seconds.']]],
        500 => ['Server error', [['server_error', 'Our side. Quote the `reference`.']]],
    ];

    public static function baseUrl(): string
    {
        $raw = rtrim((string) config('app.url'), '/');
        $host = parse_url($raw, PHP_URL_HOST) ?: '';
        $scheme = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true) ? 'http' : 'https';

        return $scheme . '://' . preg_replace('#^https?://#', '', $raw) . '/api/v';
    }

    public static function schemas(): array
    {
        return Doc::schemas() + [
            'PageMeta' => S::obj(['page' => S::int('Current page', 1), 'per_page' => S::int('Rows per page', 25), 'total' => S::int('Rows in all pages', 120), 'last_page' => S::int('Last page number', 5)], ['page', 'per_page', 'total', 'last_page']),
            'Error' => S::obj([
                'error' => S::obj([
                    'code' => S::str('A stable, machine-readable code. Branch on this, not on the message.', 'validation_failed'),
                    'message' => S::str('A sentence a person can read.', 'The email field is required.'),
                    'fields' => ['type' => 'object', 'description' => 'Only on validation errors: field name to a list of problems.', 'additionalProperties' => S::arr(S::str()), 'example' => ['email' => ['The email field is required.']]],
                ], ['code', 'message']),
            ], ['error']),
        ];
    }

    public static function spec(): array
    {
        $schemas = self::schemas();
        $paths = [];
        foreach (Doc::ops() as $op) {
            $paths[self::pathKey($op)][strtolower($op->method)] = self::operation($op, $schemas);
        }
        ksort($paths);

        $tags = [];
        foreach (Doc::tags() as $name => $desc) {
            $tags[] = ['name' => $name, 'description' => $desc];
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Tsoka Vendor API',
                'version' => ApiVersion::CURRENT,
                'summary' => 'Everything a vendor can do in the portal, over HTTP.',
                'description' => "Run your listings, seats, bookings, guests, payments, loyalty, waitlist, invoices, messages and reports from your own systems.\n\nAuthenticate with `Authorization: Bearer <key>`. Test keys (`sk_test_`, `pk_test_`) work without a plan and **never send anything to a guest** (no e-mail, message or payment), but they read and write your real data, so create test bookings with a test address and cancel them afterwards. See the *Test mode* guide. Every response is scoped to the account that owns the key.",
            ],
            'servers' => [['url' => self::baseUrl(), 'description' => 'This portal']],
            'tags' => $tags,
            'security' => [['ApiKey' => []]],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'ApiKey' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Your API key: `sk_live_…` / `sk_test_…` (read and write, keep on your server) or `pk_live_…` / `pk_test_…` (read only, safe in a browser).'],
                    'GuestToken' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Customer-Token', 'description' => 'The token returned when a guest signs in. Sent in addition to the API key on guest endpoints.'],
                ],
                'schemas' => $schemas,
            ],
            'x-scopes' => \App\Support\ApiScopes::AREAS,
        ];
    }

    private static function pathKey(Op $op): string
    {
        return $op->pathTemplate;
    }

    private static function operation(Op $op, array $schemas): array
    {
        $params = [];
        foreach (self::pathParams($op) as $name => [$type, $desc]) {
            $params[] = ['name' => $name, 'in' => 'path', 'required' => true, 'description' => $desc, 'schema' => ['type' => $type]];
        }
        foreach ($op->query as $q) {
            $schema = ['type' => $q['type']];
            foreach (['enum', 'default', 'format', 'min'] as $k) {
                if (isset($q[$k])) {
                    $schema[$k === 'min' ? 'minimum' : $k] = $q[$k];
                }
            }
            $params[] = array_filter(['name' => $q['name'], 'in' => 'query', 'required' => (bool) ($q['required'] ?? false), 'description' => $q['desc'], 'schema' => $schema, 'example' => $q['example'] ?? null], fn ($v) => $v !== null);
        }

        $o = [
            'operationId' => $op->id ?: self::operationId($op),
            'tags' => [$op->tag],
            'summary' => $op->summary,
            'description' => trim($op->description . ($op->notes ? "\n\n" . implode("\n\n", $op->notes) : '')),
            'x-scope' => $op->scope,
            'x-write' => $op->isWrite(),
        ];
        if ($op->legacy) {
            $o['x-legacy-shape'] = $op->legacy;
        }
        if ($params) {
            $o['parameters'] = $params;
        }
        $o['security'] = $op->auth === 'none' ? [] : ($op->auth === 'key+guest' ? [['ApiKey' => [], 'GuestToken' => []]] : [['ApiKey' => []]]);

        if ($op->body) {
            $o['requestBody'] = ['required' => $op->bodyRequired, 'content' => ['application/json' => ['schema' => $op->body, 'example' => S::example($op->body, $schemas)]]];
        }
        if ($op->isWrite()) {
            $o['x-idempotent'] = true;
        }

        $o['responses'] = self::responses($op, $schemas);
        $o['x-codeSamples'] = self::samples($op, $schemas);

        return $o;
    }

    private static function responses(Op $op, array $schemas): array
    {
        $r = [];
        foreach ($op->returns as $status => $ret) {
            $entry = ['description' => $ret['desc'] !== '' ? $ret['desc'] : (self::STATUS_TEXT[$status] ?? 'Success')];
            if ($op->binary) {
                $entry['content'] = [$ret['desc'] ?: 'application/octet-stream' => ['schema' => $ret['schema']]];
            } elseif ($ret['schema'] !== null) {
                $entry['content'] = ['application/json' => ['schema' => $ret['schema'], 'example' => $ret['example'] ?? S::example($ret['schema'], $schemas)]];
            }
            $r[(string) $status] = $entry;
        }

        // What can go wrong: the standard ones that apply, then this endpoint's own.
        $std = [401, 429, 500];
        if ($op->isWrite() || $op->scope) {
            $std[] = 403;
        }
        $std[] = 402;
        if (preg_match('/\{/', $op->pathTemplate)) {
            $std[] = 404;
        }
        if ($op->body || $op->isWrite()) {
            $std[] = 422;
        }
        $byStatus = [];
        foreach (self::STD_ERRORS as $status => [$title, $codes]) {
            if (in_array($status, $std, true)) {
                foreach ($codes as [$code, $when]) {
                    $byStatus[$status][] = [$code, $when];
                }
            }
        }
        foreach ($op->errors as $code => [$status, $when]) {
            $byStatus[$status][] = [$code, $when];
        }
        ksort($byStatus);
        foreach ($byStatus as $status => $codes) {
            $examples = [];
            foreach ($codes as [$code, $when]) {
                $examples[$code] = ['summary' => $code, 'description' => $when, 'value' => ['error' => ['code' => $code, 'message' => $when] + ($code === 'validation_failed' ? ['fields' => ['email' => ['The email field is required.']]] : [])]];
            }
            $r[(string) $status] = [
                'description' => (self::STD_ERRORS[$status][0] ?? self::STATUS_TEXT[$status] ?? 'Error') . ': ' . implode('; ', array_map(fn ($c) => "`{$c[0]}` {$c[1]}", $codes)),
                'content' => ['application/json' => ['schema' => self::errorRef(), 'examples' => $examples]],
            ];
        }
        ksort($r);

        return $r;
    }

    private static function errorRef(): array
    {
        return ['$ref' => '#/components/schemas/Error'];
    }

    private const STATUS_TEXT = [200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No content', 400 => 'Bad request', 409 => 'Conflict', 422 => 'Validation failed', 502 => 'Bad gateway', 503 => 'Unavailable'];

    private static function pathParams(Op $op): array
    {
        preg_match_all('/\{(\w+)\}/', $op->pathTemplate, $m);
        $out = [];
        foreach ($m[1] as $name) {
            $out[$name] = $op->path[$name] ?? match ($name) {
                'id' => ['integer', 'The id.'],
                'code' => ['string', 'The booking code (from the booking\'s `code`).'],
                'type' => ['string', 'The service type: tour, hotel, car, boat, space, event, flight or visa.'],
                default => ['string', ucfirst(str_replace('_', ' ', $name)) . '.'],
            };
        }

        return $out;
    }

    private static function operationId(Op $op): string
    {
        $words = preg_split('/[^a-zA-Z0-9]+/', strtolower($op->method . ' ' . preg_replace('/\{(\w+)\}/', 'by_$1', $op->pathTemplate)), -1, PREG_SPLIT_NO_EMPTY);

        return lcfirst(implode('', array_map('ucfirst', $words)));
    }

    // ── Code samples ──────────────────────────────────────────────────────────

    public static function example(Op $op, array $schemas): array
    {
        $path = $op->pathTemplate;
        foreach (self::pathParams($op) as $name => [$type]) {
            $path = str_replace('{' . $name . '}', $type === 'integer' ? '42' : ($name === 'code' ? 'ABC123' : ($name === 'type' ? 'tour' : 'example')), $path);
        }
        $query = [];
        foreach ($op->query as $q) {
            if (($q['required'] ?? false) || (isset($q['example']) && count($query) < 2)) {
                $query[$q['name']] = $q['example'] ?? ($q['enum'][0] ?? 'value');
            }
        }
        $body = $op->body ? S::example($op->body, $schemas) : null;

        return [$path . ($query ? '?' . http_build_query($query) : ''), $body];
    }

    private static function samples(Op $op, array $schemas): array
    {
        [$path, $body] = self::example($op, $schemas);
        $url = self::baseUrl() . $path;
        $key = $op->isWrite() ? 'sk_test_xxxxxxxxxxxxxxxx' : 'pk_test_xxxxxxxxxxxxxxxx';
        $guest = $op->auth === 'key+guest';
        $json = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;

        $noKey = $op->auth === 'none';
        $curl = "curl -X {$op->method} \"{$url}\"" . ($noKey ? '' : " \\\n  -H \"Authorization: Bearer {$key}\"") . ($guest ? " \\\n  -H \"X-Customer-Token: 12|abc...\"" : '')
            . ($op->isWrite() ? " \\\n  -H \"Idempotency-Key: $(uuidgen)\"" : '') . ($json ? " \\\n  -H \"Content-Type: application/json\" \\\n  -d '" . json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "'" : '');

        $hdr = ($noKey ? '' : "    Authorization: 'Bearer {$key}',\n") . ($guest ? "    'X-Customer-Token': token,\n" : '') . ($op->isWrite() ? "    'Idempotency-Key': crypto.randomUUID(),\n" : '') . ($json ? "    'Content-Type': 'application/json',\n" : '');
        $js = "const res = await fetch('{$url}', {\n  method: '{$op->method}',\n  headers: {\n{$hdr}  },\n" . ($json ? '  body: JSON.stringify(' . preg_replace('/\n/', "\n  ", $json) . "),\n" : '') . "});\nconst { data, error } = await res.json();";

        $phpHdr = ($noKey ? '' : "    'Authorization: Bearer {$key}',\n") . ($guest ? "    'X-Customer-Token: ' . \$token,\n" : '') . ($op->isWrite() ? "    'Idempotency-Key: ' . bin2hex(random_bytes(16)),\n" : '') . ($json ? "    'Content-Type: application/json',\n" : '');
        $php = "\$ch = curl_init('{$url}');\ncurl_setopt_array(\$ch, [\n  CURLOPT_CUSTOMREQUEST => '{$op->method}',\n  CURLOPT_RETURNTRANSFER => true,\n  CURLOPT_HTTPHEADER => [\n{$phpHdr}  ],\n" . ($json ? "  CURLOPT_POSTFIELDS => json_encode(" . self::phpArray($body) . "),\n" : '') . "]);\n\$result = json_decode(curl_exec(\$ch), true);";

        $pyHdr = ($noKey ? '' : "    'Authorization': 'Bearer {$key}',\n") . ($guest ? "    'X-Customer-Token': token,\n" : '') . ($op->isWrite() ? "    'Idempotency-Key': str(uuid.uuid4()),\n" : '');
        $py = ($op->isWrite() ? "import uuid\n" : '') . "import requests\n\nres = requests.request('{$op->method}', '{$url}',\n  headers={\n{$pyHdr}  }" . ($json ? ",\n  json=" . preg_replace('/\n/', "\n  ", $json) : '') . ")\nresult = res.json()";

        return [
            ['lang' => 'cURL', 'label' => 'cURL', 'source' => $curl],
            ['lang' => 'JavaScript', 'label' => 'JavaScript', 'source' => $js],
            ['lang' => 'PHP', 'label' => 'PHP', 'source' => $php],
            ['lang' => 'Python', 'label' => 'Python', 'source' => $py],
        ];
    }

    private static function phpArray(mixed $v, int $indent = 2): string
    {
        $pad = str_repeat(' ', $indent);
        if (is_array($v)) {
            $isList = array_is_list($v);
            $rows = [];
            foreach ($v as $k => $x) {
                $rows[] = $pad . '  ' . ($isList ? '' : var_export($k, true) . ' => ') . self::phpArray($x, $indent + 2);
            }

            return "[\n" . implode(",\n", $rows) . "\n{$pad}]";
        }

        return var_export($v, true);
    }

    // ── Postman ───────────────────────────────────────────────────────────────

    public static function postman(): array
    {
        $schemas = self::schemas();
        $folders = [];
        foreach (Doc::ops() as $op) {
            [$path, $body] = self::example($op, $schemas);
            $url = '{{base_url}}' . preg_replace('/\?.*/', '', $path);
            $item = [
                'name' => $op->summary ?: $op->key(),
                'request' => [
                    'method' => $op->method,
                    'header' => array_values(array_filter([
                        $op->auth === 'none' ? null : ['key' => 'Authorization', 'value' => 'Bearer {{api_key}}'],
                        $op->auth === 'key+guest' ? ['key' => 'X-Customer-Token', 'value' => '{{guest_token}}'] : null,
                        $op->isWrite() ? ['key' => 'Idempotency-Key', 'value' => '{{$guid}}'] : null,
                        $body !== null ? ['key' => 'Content-Type', 'value' => 'application/json'] : null,
                    ])),
                    'url' => ['raw' => $url . (str_contains($path, '?') ? '?' . explode('?', $path, 2)[1] : ''), 'host' => ['{{base_url}}'], 'path' => array_values(array_filter(explode('/', preg_replace('/\?.*/', '', $path))))],
                    'description' => trim($op->description . ($op->scope ? "\n\nScope: `{$op->scope}`" : '')),
                ] + ($body !== null ? ['body' => ['mode' => 'raw', 'raw' => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 'options' => ['raw' => ['language' => 'json']]]] : []),
            ];
            $folders[$op->tag][] = $item;
        }
        $items = [];
        foreach ($folders as $tag => $list) {
            $items[] = ['name' => $tag, 'description' => Doc::tags()[$tag] ?? '', 'item' => $list];
        }

        return [
            'info' => ['name' => 'Tsoka Vendor API', 'description' => 'Import, set `api_key` to a test key, and try any endpoint.', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'],
            'variable' => [['key' => 'base_url', 'value' => self::baseUrl()], ['key' => 'api_key', 'value' => 'sk_test_xxxxxxxxxxxxxxxx'], ['key' => 'guest_token', 'value' => '']],
            'item' => $items,
        ];
    }
}
