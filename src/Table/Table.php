<?php

namespace WKorbecki\Table\Table;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use WKorbecki\Table\Filter\Filter;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Html\Button;

abstract class Table extends DataTable {
    protected string $name;
    protected array $buttons = [];
    protected bool $searching = true;
    protected bool $paging = true;
    protected bool $lengthChange = true;
    protected bool $stateSave = true;
    protected bool $fixedHeader = true;
    protected int $searchDelay = 350;
    protected int $orderIndex = 1;
    protected string $orderDirection = 'desc';
    protected ?string $filterClass = null;
    protected ?string $filterButtonAction = null;
    protected ?string $filterButtonName = null;
    protected ?string $reloadButtonName = 'Button';

    /**
     * @var Column[]
     */
    private array $columns = [];
    private ?Filter $filter = null;

    public function __construct() {
        if ($this->filterClass) {
            $this->filter = new $this->filterClass();
            $this->filter->init();
        }
    }

    public function datatableQuery(QueryBuilder $query) : DataTableAbstract {
        $this->filterApply($query);

        return $this->datatable(datatables()->query($query));
    }

    public function datatableEloquent(EloquentBuilder $query) : DataTableAbstract {
        $this->filterApply($query);

        return $this->datatable(datatables()->eloquent($query));
    }

    public function datatable(QueryDataTable $datatable) : QueryDataTable {
        $raw = [];

        foreach ($this->columns as $column) {
            $datatable = $datatable
                ->addColumn($column->id(), static fn ($item) => $column->render($item))
                ->filterColumn($column->id(), static fn ($query, $keyword) => $column->filter($query, $keyword));
            $raw[] = $column->id();
        }

        return $datatable->rawColumns($raw);
    }

    public function filter() : ?Filter {
        return $this->filter;
    }

    private function filterApply(& $query) {
        if ($this->filter) {
            $this->filter->filter($query);
        }
    }

    public function html() : Builder {
        $this->lengthChange = $this->paging ? $this->lengthChange : false;
        $buttons = $this->buttons();

        $builder = $this->builder();
        $builder->setTableId(Str::lower($this->name) . '-table');
        $builder->columns(collect($this->columns)->map(static fn (Column $column) => $column->make())->toArray());
        $builder->searching($this->searching);
        $builder->paging($this->paging);
        $builder->lengthChange($this->lengthChange);
        $builder->stateSave($this->stateSave);
        $builder->fixedHeader($this->fixedHeader);
        $builder->searchDelay($this->searchDelay);
        $builder->orderBy($this->orderIndex, $this->orderDirection);
        $builder->buttons($buttons);
        $builder->postAjax();
        $builder->dom("<'card-table-header'<'row align-items-center'<'col-12 col-sm-6 col-md-3 order-0'".($this->lengthChange ? 'l' : '').">
        <'col-12 col-md-6 order-2 order-md-1 text-center'" . ($buttons ? 'B' : '') . ">
        <'col-12 col-sm-6 col-md-3 order-1 order-md-2'" . ($this->searching ? 'f' : '') . ">>>
        <'table-responsive't>
        <'card-table-footer'<'row align-items-center'<'col-sm-12 col-md-5'i>
        <'col-sm-12 col-md-7'".($this->paging ? 'p' : '').">>>r");

        return $builder;
    }

    private function buttons() : array {
        $buttons = [
            Button::make()->action('function(e, table) { table.ajax.reload(); }')->text($this->reloadButtonName),
        ];

        if ($this->filter) {
            $buttons[] = Button::make()->action($this->filterButtonAction)->text($this->filterButtonName);
        }

        return [...$buttons, ... $this->buttons];
    }

    protected function addColumnRaw(string $id, string $title, bool $searchable, bool $orderable, $render = null, $filter = null) {
        $this->addColumn(new Column(
            $id,
            $title,
            $searchable,
            $orderable,
            $render,
            $filter
        ));
    }

    protected function addColumn(Column $column) {
        $this->columns[$column->id()] = $column;
    }
}