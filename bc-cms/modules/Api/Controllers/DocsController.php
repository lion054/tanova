<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Api\Docs\OpenApi;
use Modules\Api\Docs\Reference;

/** The public API documentation: the reference page, the OpenAPI file and the Postman collection. No key needed. */
class DocsController extends Controller
{
    public function page(Request $request)
    {
        return view('api-docs.public', Reference::page((string) $request->query('section', '')));
    }

    /** Swagger UI over the same spec, for people who want to browse and try it in the standard tool. */
    public function swagger(Request $request)
    {
        return view('api-docs.swagger', ['specUrl' => $this->base($request) . '/openapi.json']);
    }

    public function openapi(Request $request)
    {
        $spec = OpenApi::spec();
        // The server is the host this was asked from, so "Try it out" in Swagger hits the right place.
        $spec['servers'] = [['url' => $this->base($request), 'description' => 'This portal']];

        return $this->json($spec, $request->boolean('download') ? 'tsoka-vendor-api.openapi.json' : null);
    }

    public function postman(Request $request)
    {
        $c = OpenApi::postman();
        foreach ($c['variable'] as &$v) {
            if ($v['key'] === 'base_url') {
                $v['value'] = $this->base($request);
            }
        }

        return $this->json($c, $request->boolean('download') ? 'tsoka-vendor-api.postman_collection.json' : null);
    }

    /** This host's API address. Behind the proxy the request looks like http, so anything but a local host is https. */
    private function base(Request $request): string
    {
        $host = $request->getHttpHost();
        $local = in_array($request->getHost(), ['localhost', '127.0.0.1', '0.0.0.0'], true);

        return ($local ? $request->getScheme() : 'https') . '://' . $host . '/api/v';
    }

    private function json(array $doc, ?string $download = null)
    {
        $body = json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $headers = ['Content-Type' => 'application/json', 'Cache-Control' => 'public, max-age=300', 'ETag' => '"' . md5($body) . '"', 'Access-Control-Allow-Origin' => '*'];
        if ($download) {
            $headers['Content-Disposition'] = 'attachment; filename="' . $download . '"';
        }

        return response($body, 200, $headers);
    }
}
