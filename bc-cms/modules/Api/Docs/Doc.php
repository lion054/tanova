<?php

namespace Modules\Api\Docs;

/**
 * The single description of the API. Each endpoint is declared once, here, and the OpenAPI file, the
 * reference page, the Postman collection, the examples and the drift tests are all built from it.
 *
 *   Doc::op('GET', '/loyalty/members')->tag('Loyalty')->scope('loyalty:read')
 *      ->summary('List members')->description('...')->query([P::q('name or e-mail'), ...])
 *      ->returns(200, S::page('LoyaltyMember'));
 */
class Doc
{
    /** @var Op[] keyed "METHOD /path" */
    private static array $ops = [];
    private static array $schemas = [];
    private static array $tags = [];
    private static bool $loaded = false;

    public static function tag(string $name, string $description): void
    {
        self::$tags[$name] = $description;
    }

    public static function schema(string $name, array $schema): void
    {
        self::$schemas[$name] = $schema;
    }

    public static function op(string $method, string $path): Op
    {
        $op = new Op(strtoupper($method), '/' . trim($path, '/'));

        return self::$ops[$op->key()] = $op;
    }

    /** Loads every definition file once. */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        $files = glob(__DIR__ . '/Definitions/*.php') ?: [];
        sort($files);
        foreach ($files as $f) {
            require $f;
        }
    }

    /** @return Op[] */
    public static function ops(): array { self::load(); return self::$ops; }
    public static function schemas(): array { self::load(); return self::$schemas; }
    public static function tags(): array { self::load(); return self::$tags; }
}
