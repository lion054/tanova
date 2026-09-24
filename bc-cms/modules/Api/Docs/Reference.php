<?php

namespace Modules\Api\Docs;

use Illuminate\Support\Str;

/**
 * Turns the OpenAPI document into what the reference page shows: guides rendered from markdown, and for each
 * endpoint its parameters, field tables, examples and errors. It reads the spec, never the controllers, so the page
 * cannot say anything the spec does not.
 */
class Reference
{
    /** @return array{guides: array, tags: array, spec: array} */
    public static function model(): array
    {
        $spec = OpenApi::spec();
        $components = $spec['components']['schemas'];

        $byTag = [];
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $op) {
                $byTag[$op['tags'][0]][] = self::operation(strtoupper($method), $path, $op, $components);
            }
        }
        $tags = [];
        foreach ($spec['tags'] as $t) {
            if (!empty($byTag[$t['name']])) {
                $ops = $byTag[$t['name']];
                usort($ops, fn ($a, $b) => [$a['order'], $a['path'], $a['method']] <=> [$b['order'], $b['path'], $b['method']]);
                $tags[] = ['name' => $t['name'], 'slug' => Str::slug($t['name']), 'description' => self::md($t['description']), 'ops' => $ops];
            }
        }

        return ['guides' => self::guides(), 'tags' => $tags, 'spec' => $spec, 'scopes' => $spec['x-scopes'], 'errors' => self::errorCatalogue($tags)];
    }

    /** @return array<int,array{slug:string,title:string,html:string}> */
    public static function guides(): array
    {
        $out = [];
        $files = glob(__DIR__ . '/guides/*.md') ?: [];
        sort($files);
        foreach ($files as $f) {
            $raw = (string) file_get_contents($f);
            $title = preg_match('/^#\s+(.+)$/m', $raw, $m) ? trim($m[1]) : basename($f, '.md');
            $body = preg_replace('/^#\s+.+\n/', '', $raw, 1);
            $out[] = ['slug' => 'guide-' . Str::slug($title), 'title' => $title, 'html' => self::md($body)];
        }

        return $out;
    }

    public static function md(?string $text): string
    {
        return $text === null || $text === '' ? '' : Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    private static function operation(string $method, string $path, array $op, array $components): array
    {
        $params = array_map(fn ($p) => [
            'name' => $p['name'], 'in' => $p['in'], 'required' => (bool) ($p['required'] ?? false), 'type' => $p['schema']['type'] ?? 'string',
            'desc' => self::inline($p['description'] ?? ''), 'enum' => $p['schema']['enum'] ?? null, 'default' => $p['schema']['default'] ?? null, 'example' => $p['example'] ?? null,
        ], $op['parameters'] ?? []);

        $body = null;
        if (isset($op['requestBody'])) {
            $schema = $op['requestBody']['content']['application/json']['schema'];
            $body = ['required' => (bool) $op['requestBody']['required'], 'rows' => self::rows($schema, $components, [], 0, $schema['required'] ?? []), 'example' => self::json($op['requestBody']['content']['application/json']['example'] ?? S::example($schema, $components))];
        }

        $responses = [];
        $errors = [];
        foreach ($op['responses'] as $status => $r) {
            $json = $r['content']['application/json'] ?? null;
            if ((int) $status < 400) {
                $schema = $json['schema'] ?? null;
                $responses[] = [
                    'status' => $status, 'desc' => $r['description'],
                    'rows' => $schema ? self::rows($schema, $components, [], 0, $schema['required'] ?? []) : [],
                    'example' => $json ? self::json($json['example'] ?? S::example($schema, $components)) : null,
                    'binary' => isset($r['content']) && !$json,
                ];
            } else {
                foreach (($json['examples'] ?? []) as $code => $e) {
                    $errors[] = ['status' => $status, 'code' => $code, 'when' => self::inline($e['description'] ?? '')];
                }
            }
        }

        return [
            'id' => $op['operationId'], 'method' => $method, 'path' => $path, 'summary' => $op['summary'], 'description' => self::md($op['description'] ?? ''),
            'scope' => $op['x-scope'] ?? null, 'write' => (bool) ($op['x-write'] ?? false), 'legacy' => $op['x-legacy-shape'] ?? null,
            'guest' => str_contains(json_encode($op['security'] ?? []), 'GuestToken'),
            'params' => $params, 'body' => $body, 'responses' => $responses, 'errors' => $errors, 'samples' => $op['x-codeSamples'] ?? [],
            'order' => ['GET' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4][$method] ?? 5,
        ];
    }

    /** Every error code in one table, with the endpoints that can return it. */
    private static function errorCatalogue(array $tags): array
    {
        $all = [];
        foreach ($tags as $t) {
            foreach ($t['ops'] as $op) {
                foreach ($op['errors'] as $e) {
                    $k = $e['status'] . ' ' . $e['code'];
                    $all[$k]['status'] = $e['status'];
                    $all[$k]['code'] = $e['code'];
                    $all[$k]['when'] ??= $e['when'];
                    $all[$k]['count'] = ($all[$k]['count'] ?? 0) + 1;
                }
            }
        }
        uasort($all, fn ($a, $b) => [$a['status'], $a['code']] <=> [$b['status'], $b['code']]);

        return array_values($all);
    }

    /**
     * A schema as table rows: name, type, required, description, allowed values. Objects inside objects and arrays
     * of objects are expanded (named schemas up to two levels, so a cycle cannot run away).
     *
     * @return array<int,array{name:string,type:string,required:bool,desc:string,enum:?array,depth:int}>
     */
    public static function rows(array $schema, array $components, array $seen = [], int $depth = 0, array $required = [], string $prefix = ''): array
    {
        $schema = self::resolve($schema, $components, $seen, $ref);
        if ($ref !== null && $depth >= 3) {
            return [];
        }
        // { data: X, meta: … } is common: show the fields of `data.` in the table like everywhere else.
        $props = $schema['properties'] ?? [];
        $req = $schema['required'] ?? $required;
        $rows = [];
        foreach ($props as $name => $p) {
            $full = $prefix . $name;
            $child = self::resolve($p, $components, $seen, $childRef);
            [$type, $nullable] = self::typeOf($p, $components);
            $rows[] = ['name' => $full, 'type' => $type, 'required' => in_array($name, (array) $req, true), 'desc' => self::inline($child['description'] ?? ($p['description'] ?? '')), 'enum' => $child['enum'] ?? null, 'depth' => $depth, 'example' => $child['example'] ?? null];

            $inner = $child;
            $suffix = '.';
            if (($child['type'] ?? null) === 'array' && isset($child['items'])) {
                $inner = self::resolve($child['items'], $components, $seen, $itemRef);
                $childRef = $itemRef ?? $childRef;
                $suffix = '[].';
            }
            if (isset($inner['properties']) && ($childRef === null || !in_array($childRef, $seen, true)) && $depth < 3) {
                $rows = array_merge($rows, self::rows($inner, $components, $childRef ? array_merge($seen, [$childRef]) : $seen, $depth + 1, $inner['required'] ?? [], $full . $suffix));
            }
        }
        // A bare array response: describe its items.
        if (!$props && ($schema['type'] ?? null) === 'array' && isset($schema['items'])) {
            $rows = self::rows($schema['items'], $components, $seen, $depth, [], $prefix . '[].');
        }

        return $rows;
    }

    /** Follows $ref, oneOf-with-null and allOf to the schema that has the fields. */
    private static function resolve(array $s, array $components, array $seen, ?string &$ref = null): array
    {
        $ref = null;
        for ($i = 0; $i < 6; $i++) {
            if (isset($s['$ref'])) {
                $ref = substr($s['$ref'], strlen('#/components/schemas/'));
                $s = $components[$ref] ?? [];
            } elseif (isset($s['oneOf'])) {
                $non = array_values(array_filter($s['oneOf'], fn ($x) => ($x['type'] ?? null) !== 'null'));
                $s = $non[0] ?? [];
            } elseif (isset($s['allOf'])) {
                $merged = ['type' => 'object', 'properties' => [], 'required' => []];
                foreach ($s['allOf'] as $part) {
                    $part = self::resolve($part, $components, $seen);
                    $merged['properties'] = array_merge($merged['properties'], $part['properties'] ?? []);
                    $merged['required'] = array_merge($merged['required'], $part['required'] ?? []);
                    $merged['description'] ??= $part['description'] ?? null;
                }
                $s = array_filter($merged, fn ($v) => $v !== null && $v !== []);
            } else {
                break;
            }
        }

        return $s;
    }

    /** @return array{0:string,1:bool} */
    private static function typeOf(array $p, array $components): array
    {
        $nullable = false;
        if (isset($p['oneOf'])) {
            $nullable = (bool) array_filter($p['oneOf'], fn ($x) => ($x['type'] ?? null) === 'null');
            $p = array_values(array_filter($p['oneOf'], fn ($x) => ($x['type'] ?? null) !== 'null'))[0] ?? [];
        }
        if (isset($p['$ref'])) {
            return [substr($p['$ref'], strlen('#/components/schemas/')) . ($nullable ? ' or null' : ''), $nullable];
        }
        if (isset($p['allOf'])) {
            return ['object', $nullable];
        }
        $types = (array) ($p['type'] ?? 'object');
        if (in_array('null', $types, true)) {
            $nullable = true;
            $types = array_values(array_diff($types, ['null']));
        }
        $t = $types[0] ?? 'object';
        if ($t === 'array') {
            [$inner] = self::typeOf($p['items'] ?? ['type' => 'string'], $components);
            $t = "array of {$inner}";
        } elseif (isset($p['format'])) {
            $t .= " ({$p['format']})";
        }

        return [$t . ($nullable ? ' or null' : ''), $nullable];
    }

    private static function inline(string $text): string
    {
        $html = self::md($text);

        return trim(preg_replace('#^<p>(.*)</p>$#s', '$1', trim($html)));
    }

    private static function json(mixed $v): string
    {
        return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** The model, cached until a definition or guide file changes. */
    public static function cached(): array
    {
        $stamp = md5(implode('|', array_map('filemtime', array_merge(glob(__DIR__ . '/Definitions/*.php') ?: [], glob(__DIR__ . '/guides/*.md') ?: [], [__FILE__, __DIR__ . '/OpenApi.php']))));

        return \Illuminate\Support\Facades\Cache::remember('api_docs_model:' . $stamp . ':' . config('app.url'), 3600, fn () => self::model());
    }

    /** Everything the reference view needs for one section (a guide slug, a tag slug or "errors"). */
    public static function page(?string $section): array
    {
        $doc = self::cached();
        $index = [];
        foreach ($doc['tags'] as $t) {
            foreach ($t['ops'] as $op) {
                $index[] = ['id' => $op['id'], 'method' => $op['method'], 'path' => $op['path'], 'summary' => $op['summary'], 'tag' => $t['name'], 'slug' => $t['slug']];
            }
        }
        $current = null;
        foreach ($doc['guides'] as $g) {
            if ($g['slug'] === $section) {
                $current = ['kind' => 'guide', 'guide' => $g];
            }
        }
        foreach ($doc['tags'] as $t) {
            if ($t['slug'] === $section) {
                $current = ['kind' => 'tag', 'tag' => $t];
            }
        }
        if ($section === 'errors') {
            $current = ['kind' => 'errors'];
        }
        if (!$current) {
            $section = $doc['guides'][0]['slug'] ?? 'errors';
            $current = isset($doc['guides'][0]) ? ['kind' => 'guide', 'guide' => $doc['guides'][0]] : ['kind' => 'errors'];
        }

        return ['doc' => $doc, 'section' => $section, 'current' => $current, 'index' => $index, 'apiBase' => OpenApi::baseUrl()];
    }
}
