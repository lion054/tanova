<?php

namespace Modules\Api\Docs;

/** One endpoint, built by chaining. */
class Op
{
    public string $tag = 'General';
    public string $summary = '';
    public string $description = '';
    public ?string $scope = null;          // "bookings:write"; null = no scope needed
    public string $auth = 'key';           // key | key+guest | none
    public array $query = [];
    public array $path = [];               // name => [type, description]
    public ?array $body = null;
    public bool $bodyRequired = true;
    public array $returns = [];            // status => ['schema'=>..., 'desc'=>..., 'example'=>...]
    public array $errors = [];             // code => [status, description]
    public ?string $legacy = null;         // note: this endpoint keeps its older response shape
    public array $notes = [];
    public ?string $id = null;
    public bool $binary = false;           // returns a file, not JSON

    public function __construct(public string $method, public string $pathTemplate) {}

    public function key(): string { return $this->method . ' ' . $this->pathTemplate; }

    public function tag(string $t): self { $this->tag = $t; return $this; }
    public function summary(string $s): self { $this->summary = $s; return $this; }
    public function description(string $d): self { $this->description = $d; return $this; }
    public function scope(?string $s): self { $this->scope = $s; return $this; }
    public function auth(string $a): self { $this->auth = $a; return $this; }
    public function query(array $params): self { $this->query = array_merge($this->query, $params); return $this; }
    public function path(array $params): self { $this->path = $params + $this->path; return $this; }
    public function body(array $schema, bool $required = true): self { $this->body = $schema; $this->bodyRequired = $required; return $this; }
    public function legacy(string $note): self { $this->legacy = $note; return $this; }
    public function note(string $n): self { $this->notes[] = $n; return $this; }
    public function id(string $id): self { $this->id = $id; return $this; }
    public function file(string $mime): self { $this->binary = true; $this->returns[200] = ['schema' => ['type' => 'string', 'format' => 'binary'], 'desc' => $mime, 'example' => null]; return $this; }

    public function returns(int $status, ?array $schema = null, string $desc = '', mixed $example = null): self
    {
        $this->returns[$status] = ['schema' => $schema, 'desc' => $desc, 'example' => $example];

        return $this;
    }

    /** @param array<string,array{0:int,1:string}> $errors code => [http status, when it happens] */
    public function errors(array $errors): self { $this->errors = $errors + $this->errors; return $this; }

    public function isWrite(): bool { return $this->method !== 'GET'; }
}
