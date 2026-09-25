<?php

namespace Pro\Integrations\Services;

/**
 * The integrations hub is one set of pages served at two addresses: /admin/integrations and /user/integrations (a business's own).
 * Both run the same controller and views; this says which route names to link to from inside them, so a page never sends a business
 * to the other address. Credentials are always the signed-in person's own (see Integration::forVendor).
 */
class IntegrationsNav
{
    private function __construct(public readonly string $ns)
    {
    }

    public static function current(): self
    {
        return new self(request()->is('user/*') ? 'user' : 'admin');
    }

    public static function for(string $ns): self
    {
        return new self($ns === 'user' ? 'user' : 'admin');
    }

    public function isUser(): bool
    {
        return $this->ns === 'user';
    }

    public function hub(): string
    {
        return route($this->isUser() ? 'user.integrations.index' : 'admin.integrations.hub');
    }

    public function category(string $cat): string
    {
        return route($this->ns . '.integrations.category', $cat);
    }

    public function connect(string $slug): string
    {
        return route($this->isUser() ? 'user.integrations.app.connect' : 'admin.integrations.connect', $slug);
    }

    public function disconnect(string $slug): string
    {
        return route($this->isUser() ? 'user.integrations.app.disconnect' : 'admin.integrations.disconnect', $slug);
    }

    public function test(string $slug): string
    {
        return route($this->isUser() ? 'user.integrations.app.test' : 'admin.integrations.test', $slug);
    }

    public function wetuItineraries(): string
    {
        return route($this->ns . '.integrations.wetu.itineraries');
    }

    public function wetuSync(): string
    {
        return route($this->ns . '.integrations.wetu.sync');
    }

    public function wetuImport(string $id): string
    {
        return route($this->ns . '.integrations.wetu.import', $id);
    }
}
