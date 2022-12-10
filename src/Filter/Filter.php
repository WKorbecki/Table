<?php

namespace WKorbecki\Table\Filter;

use Illuminate\Support\Facades\Session;

abstract class Filter {
    private static string $name;
    private string $customName = '';
    /**
     * @var Element[]
     */
    private array $elements = [];
    private array $search = [];
    protected string $view;

    public function addElement(Element $element) {
        $this->elements[$element->id()] = $element;
    }

    public function name() : string {
        if (!static::$name) {
            static::$name = rtrim(implode('-', [
                md5(json_encode($this->elements)),
                $this->customName,
            ]), '-');
        }

        return static::$name;
    }

    public function filter(& $query) {
        foreach ($this->elements as $element) {
            $element->filter($query, $this->elementValue($element));
        }
    }

    public function render() : string {
        return view($this->view, [
            'fields' => collect($this->elements)
                ->map(fn (Element $element) => $element
                    ->render($this->elementValue($element)))
                ->toArray(),
        ])->render();
    }

    public function isUsed() : bool {
        foreach ($this->elements as $element) {
            if ($element->isUsed($this->elementValue($element))) {
                return true;
            }
        }

        return false;
    }

    public function countUsed() : int {
        $used = 0;

        foreach ($this->elements as $element) {
            if ($element->isUsed($this->elementValue($element))) {
                $used++;
            }
        }

        return $used;
    }

    public function init() {
        $this->search = \session()->get('filter.' . $this->name(), $this->search);
    }

    private function elementValue(Element $element) {
        return $this->search[$element->id()] ?? $element->default();
    }

    public static function set(string $name, array $values) {
        Session::put('filter.' . $name, $values);
        Session::save();
    }

    public static function reset(string $name) {
        Session::forget('filter.' . $name);
        Session::save();
    }
}