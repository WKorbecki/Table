<?php

namespace WKorbecki\Table\Table;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use WKorbecki\Table\Filter\Filter;
use Yajra\DataTables\DataTableAbstract;
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
    protected ?string $filterClass;
    protected ?string $filterButtonAction;
    protected string $filterButtonName;

    /**
     * @var Column[]
     */
    private array $columns = [];
    private ?Filter $filter;

    public function __construct() {
        if ($this->filterClass) {
            $this->filter = new $this->filterClass();
            $this->filter->init();
        }
    }

    public function datatableQuery(QueryBuilder $query) : DataTableAbstract {
        $this->filter($query);

        return $this->datatable(\datatables()->query($query));
    }

    public function datatableEloquent(EloquentBuilder $query) : DataTableAbstract {
        $this->filter($query);

        return $this->datatable(\datatables()->eloquent($query));
    }

    private function datatable(DataTableAbstract $datatable) : DataTableAbstract {
        $raw = [];

        foreach ($this->columns as $column) {
            $datatable = $datatable
                ->addColumn($column->id(), static fn ($item) => $column->render($item))
                ->filterColumn($column->id(), static fn ($query, $keyword) => $column->filter($query, $keyword));
            $raw[] = $column->id();
        }

        return $datatable->rawColumns($raw);
    }

    private function filter(& $query) {
        if ($this->filter) {
            $this->filter->filter($query);
        }
    }

    public function html() : Builder {
        $builder = $this->getHtmlBuilder();
        $builder->setTableId(Str::lower($this->name) . '-table');
        $builder->columns(collect($this->columns)->map(static fn (Column $column) => $column->make())->toArray());
        $builder->searching($this->searching);
        $builder->paging($this->paging);
        $builder->lengthChange($this->paging ? $this->lengthChange : false);
        $builder->stateSave($this->stateSave);
        $builder->fixedHeader($this->fixedHeader);
        $builder->searchDelay($this->searchDelay);
        $builder->orderBy($this->orderIndex, $this->orderDirection);
        $builder->buttons($this->buttons());

        return $builder;
    }

    private function buttons() : array {
        $buttons = [
            Button::make('reload'),
        ];

        if ($this->filter) {
            Button::make('filter')->action($this->filterButtonAction)->name($this->filterButtonName);
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