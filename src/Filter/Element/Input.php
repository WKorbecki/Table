<?php

namespace WKorbecki\Table\Filter\Element;

use WKorbecki\Table\Filter\Element;

class Input extends Element {
    private string $type = 'text';
    private bool $equal = false;

    public function __construct(
        string $id,
        string $title,
        string $type,
        ?string $class,
        ?string $relation,
        ?array $attr,
        ?array $data,
        bool $equal,
        $default = null,
        $options = null,
        ?callable $customFilter = null
    ) {
        parent::__construct(
            $id,
            $title,
            $class,
            $relation,
            $attr,
            $data,
            $default,
            $options,
            $customFilter
        );
        $this->type = $type;
        $this->equal = $equal;
    }

    public function filter(&$query, $search) {
        if ($search != $this->default) {
            $this->where($query, $this->equal ? '=' : 'like', $search);
        }
    }

    public function render($default): string {
        return implode("\n", [
            '<label>'.$this->title.'</label>',
            '<input type="'.$this->type.'" '.$this->parameters().' value="'.($default ?? $this->default).'">'
        ]);
    }

    public function isUsed($value) : bool {
        return $value != $this->default;
    }
}