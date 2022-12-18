<?php

namespace WKorbecki\Table\Filter\Element;

use WKorbecki\Table\Filter\Element;

class Select extends Element {
    private bool $multiple = false;

    public function __construct(
        string $id,
        string $title,
        ?string $class,
        ?string $relation,
        ?array $attr,
        ?array $data,
        bool $multiple,
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
        $this->multiple = $multiple;
    }

    public function filter(& $query, $search) {
        if ($this->multiple && count($search)) {
            $this->where($query, 'in', $search);
        }
        elseif ($search != $this->default) {
            $this->where($query, '=', $search);
        }
    }

    public function render($default) : string {
        $parameters = [];

        if ($this->multiple) {
            $parameters['name'] = 'filter['.$this->id.'][]';
            $parameters['multiple'] = 'multiple';
        }

        return implode("\n", [
            '<label>'.$this->title.'</label>',
            '<select '.$this->parameters($parameters).'>',
            $this->renderOptions($this->options(), $default ?? $this->default),
            '</select>',
        ]);
    }

    public function isUsed($value) : bool {
        return ($this->multiple && count($value) == 0) || ($value != $this->default);
    }

    private function renderOptions(array $options, $default) : string {
        $_options = [];

        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $_options[] = implode("\n", [
                    '<optgroup label="'.$key.'">',
                    $this->renderOptions($value, $default),
                    '</optgroup>'
                ]);
            }
            else {
                $_options[] = '<option value="'.$key.'"'.($this->isOptionSelected($value, $default) ? ' selected="selected"' : '').'>'.$value.'</option>';
            }
        }

        return implode("\n", $_options);
    }

    private function isOptionSelected($value, $default) : bool {
        return $this->multiple ? in_array($value, $default) : $value == $default;
    }

    private function options() : array {
        if (is_callable($this->options)) {
            return call_user_func($this->options);
        }

        return $this->options;
    }
}