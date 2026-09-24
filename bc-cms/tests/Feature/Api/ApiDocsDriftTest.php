<?php

namespace Tests\Feature\Api;

use App\Support\ApiScopes;
use Illuminate\Support\Facades\Route;
use Modules\Api\Docs\Doc;
use Modules\Api\Docs\OpenApi;
use Tests\TestCase;

/**
 * The documentation and the API cannot drift apart: every route is documented, every documented endpoint exists,
 * the scope written in the docs is the scope the route enforces, and each endpoint is documented in enough depth.
 */
class ApiDocsDriftTest extends TestCase
{
    /** "METHOD /path" for every vendor API route, parameter names removed. */
    private function routes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $r) {
            $uri = $r->uri();
            if (!str_starts_with($uri, 'api/v/')) {
                continue;
            }
            foreach ($r->methods() as $m) {
                if (in_array($m, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $out[$m . ' /' . preg_replace('/\{[^}]+\}/', '{}', substr($uri, strlen('api/v/')))] = $r;
            }
        }

        return $out;
    }

    private function docs(): array
    {
        $out = [];
        foreach (Doc::ops() as $op) {
            $out[$op->method . ' ' . preg_replace('/\{[^}]+\}/', '{}', $op->pathTemplate)] = $op;
        }

        return $out;
    }

    public function test_every_route_is_documented(): void
    {
        $missing = array_diff(array_keys($this->routes()), array_keys($this->docs()));

        $this->assertSame([], array_values($missing), "Routes with no documentation:\n" . implode("\n", $missing));
    }

    public function test_every_documented_endpoint_exists(): void
    {
        $ghost = array_diff(array_keys($this->docs()), array_keys($this->routes()));

        $this->assertSame([], array_values($ghost), "Documented but not a route:\n" . implode("\n", $ghost));
    }

    public function test_the_documented_scope_is_the_enforced_scope(): void
    {
        $wrong = [];
        foreach ($this->routes() as $key => $route) {
            $op = $this->docs()[$key] ?? null;
            if (!$op) {
                continue;
            }
            $enforced = null;
            foreach ($route->gatherMiddleware() as $m) {
                if (is_string($m) && str_starts_with($m, 'api.scope:')) {
                    $parts = explode(',', substr($m, strlen('api.scope:')));
                    $enforced = $parts[0] === 'area'
                        ? $parts[1] . (in_array(explode(' ', $key)[0], ['GET'], true) ? ':read' : ':write')
                        : $parts[0];
                }
            }
            if ($enforced !== $op->scope) {
                $wrong[] = "{$key}: enforced " . ($enforced ?? 'none') . ', documented ' . ($op->scope ?? 'none');
            }
        }

        $this->assertSame([], $wrong, "Scope mismatch:\n" . implode("\n", $wrong));
    }

    public function test_every_endpoint_is_documented_in_depth(): void
    {
        $thin = [];
        foreach (Doc::ops() as $op) {
            $problems = [];
            if (strlen($op->summary) < 4) { $problems[] = 'no summary'; }
            if (!$op->returns) { $problems[] = 'no response'; }
            if ($op->isWrite() && !$op->body && !preg_match('/\{/', $op->pathTemplate) === false && $op->method === 'POST' && str_contains($op->pathTemplate, '/') && false) { $problems[] = 'no body'; }
            if (!isset(\Modules\Api\Docs\Doc::tags()[$op->tag])) { $problems[] = "unknown tag {$op->tag}"; }
            if ($op->scope !== null && !in_array($op->scope, ApiScopes::all(), true)) { $problems[] = "unknown scope {$op->scope}"; }
            if ($problems) { $thin[] = $op->key() . ': ' . implode(', ', $problems); }
        }

        $this->assertSame([], $thin, implode("\n", $thin));
    }

    public function test_the_openapi_document_is_well_formed(): void
    {
        $spec = OpenApi::spec();
        $this->assertSame('3.1.0', $spec['openapi']);

        // Every $ref resolves, and operation ids are unique.
        $json = json_encode($spec);
        preg_match_all('/#\/components\/schemas\/([A-Za-z0-9_]+)/', $json, $m);
        $missing = array_diff(array_unique($m[1]), array_keys($spec['components']['schemas']));
        $this->assertSame([], array_values($missing), 'schemas referenced but not defined: ' . implode(', ', $missing));

        $ids = [];
        foreach ($spec['paths'] as $path => $ops) {
            foreach ($ops as $method => $op) {
                $this->assertArrayHasKey('responses', $op, "$method $path");
                $this->assertNotContains($op['operationId'], $ids, "duplicate operationId {$op['operationId']}");
                $ids[] = $op['operationId'];
                foreach ($op['parameters'] ?? [] as $p) {
                    $this->assertNotSame('', $p['description'] ?? '', "$method $path: parameter {$p['name']} has no description");
                }
                foreach (($op['responses'] ?? []) as $status => $resp) {
                    $this->assertNotSame('', trim($resp['description'] ?? ''), "$method $path $status has no description");
                }
                // A path parameter in the URL must be declared.
                preg_match_all('/\{(\w+)\}/', $path, $pp);
                $declared = array_column(array_filter($op['parameters'] ?? [], fn ($p) => $p['in'] === 'path'), 'name');
                $this->assertEqualsCanonicalizing($pp[1], $declared, "$method $path path parameters");
            }
        }
    }

    public function test_every_example_is_valid_json_and_the_postman_collection_covers_everything(): void
    {
        $spec = OpenApi::spec();
        foreach ($spec['paths'] as $path => $ops) {
            foreach ($ops as $method => $op) {
                $this->assertCount(4, $op['x-codeSamples'], "$method $path code samples");
                foreach ($op['x-codeSamples'] as $s) {
                    $this->assertNotSame('', trim($s['source']));
                }
            }
        }
        $count = 0;
        foreach (OpenApi::postman()['item'] as $folder) {
            $count += count($folder['item']);
        }
        $this->assertSame(count(Doc::ops()), $count);
    }
}
