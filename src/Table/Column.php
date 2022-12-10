<?php

namespace WKorbecki\Table\Table;

class Column {
    private string $id;
    private string $title;
    private bool $searchable = true;
    private bool $orderable = true;
    private $render;
    private $filter;

    public function __construct(string $id, string $title, bool $searchable, bool $orderable, $render = null, ?callable $filter = null) {
        $this->id = $id;
        $this->title = $title;
        $this->searchable = $searchable;
        $this->orderable = $orderable;
        $this->render = $render;
        $this->filter = $filter;
    }

    public function id() : string {
        return $this->id;
    }

    public function make() : array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'searchable' => $this->searchable,
            'orderable' => $this->orderable,
        ];
    }

    public function render($item) : string {
        if (is_callable($this->render)) {
            return call_user_func($this->render, $item);
        }
        elseif ($this->render === null) {
            return $item->{$this->id} ?? '';
        }

        return $this->render;
    }

    public function filter($query, $keyword) {
        if (is_callable($this->filter)) {
            call_user_func($this->filter, $query, $keyword);
        }
        else {
            $query->where($this->id, 'like', '%' . $keyword . '%');
        }
    }
}