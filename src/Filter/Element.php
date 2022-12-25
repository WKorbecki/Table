<?php

namespace WKorbecki\Table\Filter;

abstract class Element {
    protected string $id;
    protected string $title;
    protected string $class = '';
    protected ?string $relation;
    protected array $attr = [];
    protected array $data = [];
    protected $default;
    protected $options;
    protected $customFilter;

    public function __construct(
        string $id,
        string $title,
        ?string $class,
        ?string $relation,
        ?array $attr,
        ?array $data,
        $default = null,
        $options = null,
        ?callable $customFilter = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->class = $class ?? $this->class;
        $this->relation = $relation;
        $this->attr = $attr ?? $this->attr;
        $this->data = $data ?? $this->data;
        $this->default = $default;
        $this->options = $options;
        $this->customFilter = $customFilter;
    }

    public function id() : string {
        return $this->id;
    }
    public function default() {
        return $this->default;
    }

    abstract public function filter(& $query, $search);

    abstract public function render($default) : string;

    abstract public function isUsed($value) : bool;

    protected function parameters(array $parameters = []) : string {
        $this->addParameter($parameters, 'id', 'filter_'.$this->id);
        $this->addParameter($parameters, 'name', 'filter['.$this->id.']');
        $this->addParameter($parameters, 'class', $this->class);
        $this->addParameters($parameters, $this->attr);
        $this->addParameters($parameters, $this->data, 'data-');

        return collect($parameters)->map(static fn ($value, $key) => $key . '="' . $value . '"')->implode(' ');
    }

    protected final function addParameter(array & $parameters, string $key, $value, bool $overwrite = false) {
        if (!isset($parameters[$key]) || $overwrite) {
            $parameters[$key] = $value;
        }
    }

    protected final function addParameters(array & $parameters, array $new_parameters, string $prefix = '', bool $overwrite = false) {
        foreach ($new_parameters as $key => $value) {
            $this->addParameter($parameters, $prefix . $key, $value, $overwrite);
        }
    }

    protected final function where(& $query, $operator, $search) {
        if ($this->relation) {
            $query->whereHas($this->relation, function ($query) use ($operator, $search) {
                $this->whereOperator($query, $operator, $search);
            });
        }
        else {
            $this->whereOperator($query, $operator, $search);
        }
    }

    private function whereOperator(& $query, $operator, $search) {
        if (is_callable($this->customFilter)) {
            $this->whereCustom($query, $search);
        }
        else {
            switch ($operator) {
                case '=': $query->where($this->id, '=', $search); break;
                case 'like': $query->where($this->id, 'like', '%' . $search . '%'); break;
                case 'in': $query->whereIn($this->id, $search); break;
            }
        }
    }

    protected function whereCustom(& $query, $search) {
        call_user_func($this->customFilter, $this, $query, $search);
    }
}