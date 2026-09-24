<?php

namespace Tests\Feature\Api;

use Modules\Api\Docs\Reference;
use Tests\ApiTestCase;

class DocsPagesTest extends ApiTestCase
{
    public function test_the_spec_and_collection_are_public_and_valid(): void
    {
        $spec = $this->getJson('/api/v/openapi.json')->assertOk()->assertHeader('Access-Control-Allow-Origin', '*')->json();
        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertGreaterThan(100, count($spec['paths']));
        $this->assertArrayHasKey('/bookings', $spec['paths']);
        $this->getJson('/api/v/postman.json')->assertOk()->assertJsonPath('info.name', 'Tsoka Vendor API');
    }

    public function test_every_section_of_the_reference_renders(): void
    {
        $sections = array_merge(array_column(Reference::cached()['guides'], 'slug'), array_column(Reference::cached()['tags'], 'slug'), ['errors', '']);
        foreach ($sections as $s) {
            $r = $this->get('/api/v/docs?section=' . $s)->assertOk();
            $this->assertStringContainsString('Tsoka Vendor API', $r->getContent(), $s);
        }
        $this->get('/api/v/docs?section=bookings')->assertSee('bookings:write', false)->assertSee('sold_out', false);
        $this->get('/api/v/docs?section=nonsense')->assertOk();   // unknown falls back to the first guide
    }

    public function test_guides_only_name_endpoints_that_exist(): void
    {
        $ops = array_map(fn ($o) => preg_replace('/\{[^}]+\}/', '{}', $o->pathTemplate), \Modules\Api\Docs\Doc::ops());
        $known = array_flip(array_map(fn ($o) => $o->method . ' ' . preg_replace('/\{[^}]+\}/', '{}', $o->pathTemplate), \Modules\Api\Docs\Doc::ops()));
        $bad = [];
        foreach (glob(base_path('modules/Api/Docs/guides/*.md')) as $f) {
            preg_match_all('/`?\b(GET|POST|PUT|PATCH|DELETE) (\/[a-z0-9\-\/{}_.]+)/i', file_get_contents($f), $m, PREG_SET_ORDER);
            foreach ($m as [$full, $verb, $path]) {
                $path = preg_replace('#/(\d+|[A-Z0-9]{6})(?=/|$)#', '/{}', rtrim($path, '.'));   // an example id or booking code
                $key = $verb . ' ' . preg_replace('/\{[^}]+\}/', '{}', $path);
                $key = preg_replace('#^([A-Z]+) /api/v#', '$1 ', $key);
                if (str_starts_with($path, '/api/vendor/')) { continue; }   // the account API, not part of this spec
                // A documented `{param}` segment matches any literal there (`/payments/{}/approve` is `/payments/{}/{decision}`).
                $matches = isset($known[$key]) || collect(array_keys($known))->contains(fn ($k) => preg_match('#^' . str_replace('\\{\\}', '[^/]+', preg_quote($k, '#')) . '$#', $key));
                if (!$matches) { $bad[] = basename($f) . ": {$verb} {$path}"; }
            }
        }
        $this->assertSame([], array_values(array_unique($bad)), "Guides mention endpoints that are not documented:\n" . implode("\n", array_unique($bad)));
    }
}
