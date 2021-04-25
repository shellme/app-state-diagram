<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use Koriym\AppStateDiagram\Exception\InvalidSemanticsException;
use stdClass;

use function explode;
use function is_string;
use function json_encode;
use function sprintf;

abstract class AbstractDescriptor
{
    /** @var string */
    public $id;

    /** @var ?string */
    public $def;

    /** @var stdClass|null */
    public $doc;

    /** @var list<stdClass> */
    public $descriptor;

    /** @var string */
    public $type = 'semantic';

    /** @var ?string */
    public $rel;

    /** @var stdClass|SemanticDescriptor|null */
    public $parent;

    /** @var list<string> */
    public $tags;

    /** @var string */
    public $title;

    /** @var object */
    public $source;

    public function __construct(object $descriptor, ?stdClass $parentDescriptor = null)
    {
        if (! isset($descriptor->id)) {
            throw new InvalidSemanticsException((string) json_encode($descriptor));
        }

        $this->source = $descriptor;
        $this->id = (string) $descriptor->id;
        /** @psalm-suppress MixedAssignment */
        $this->def = $descriptor->def ?? $descriptor->ref ?? $descriptor->src ?? null; // @phpstan-ignore-line
        /** @psalm-suppress MixedAssignment */
        $this->doc = $descriptor->doc ?? null; // @phpstan-ignore-line
        /** @psalm-suppress MixedAssignment */
        $this->descriptor = $descriptor->descriptor ?? []; // @phpstan-ignore-line
        $this->parent = $parentDescriptor;
        /** @psalm-suppress MixedAssignment */
        $tag = $descriptor->tag ?? [];  // @phpstan-ignore-line
        /** @psalm-suppress MixedAssignment */
        $this->tags = is_string($tag) ? explode(' ', $tag) : $tag; //@phpstan-ignore-line
        /** @psalm-suppress MixedAssignment */
        $this->title = $descriptor->title ?? ''; //@phpstan-ignore-line
    }

    public function normalize(string $schema): stdClass
    {
        $alps = new stdClass();
        if ($this->doc) {
            $alps->doc = $this->doc;
        }

        $descriptor = new stdClass();
        $descriptor->id = $this->id;
        if ($this->def) {
            $descriptor->ref = $this->def;
        }

        $descriptor->type = $this->type;
        $alps->descriptor = [$descriptor];
        $alpsDoc = new stdClass();
        $alpsDoc->{'$schema'} = $schema;
        $alpsDoc->alps = $alps;
        if ($this->parent instanceof stdClass || ($this->parent instanceof SemanticDescriptor)) {
            $jsonPath = sprintf('%s.%s.json', (string) $this->parent->type, (string) $this->parent->id);
            /** @psalm-suppress MixedArrayAssignment */
            $alpsDoc->link[] = ['rel' => 'parent', 'href' => $jsonPath];
        }

        return $alpsDoc;
    }

    public function htmlLink(): string
    {
        return sprintf('[%s](%s.%s.html)', $this->id, $this->type, $this->id);
    }

    public function jsonLink(): string
    {
        return sprintf('[%s](../descriptor/%s.%s.json)', $this->id, $this->type, $this->id);
    }
}
