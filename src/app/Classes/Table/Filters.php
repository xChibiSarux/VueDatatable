<?php

namespace LaravelEnso\VueDatatable\app\Classes\Table;

use Carbon\Carbon;

class Filters
{
    private $request;
    private $query;

    public function __construct($request, $query)
    {
        $this->request = $request;
        $this->query = $query;
    }

    public function set()
    {
        $this->setSearch()
            ->setFilters()
            ->setIntervals();
    }

    private function setSearch()
    {
        if (!$this->request->has('search')) {
            return $this;
        };

        collect(explode(' ', $this->request->get('search')))->each(function ($arg) {
            $this->query->where(function ($query) use ($arg) {
                collect($this->request->get('columns'))->each(function ($column) use ($query, $arg) {
                    $column = json_decode($column);

                    if ($column->meta->searchable) {
                        $query->orWhere($column->name, 'LIKE', '%' . $arg . '%');
                    }
                });
            });
        });

        return $this;
    }

    private function setFilters()
    {
        if (!$this->request->has('filters')) {
            return $this;
        }

        $this->query->where(function ($query) {
            collect(json_decode($this->request->get('filters')))->each(function ($filters, $table) use ($query) {
                collect($filters)->each(function ($value, $column) use ($table, $query) {
                    if (!is_null($value) && $value !== '') {
                        $query->where($table . '.' . $column, '=', $value);
                    }
                });
            });
        });

        return $this;
    }

    private function setIntervals()
    {
        if (!$this->request->has('intervals')) {
            return $this;
        }

        $this->query->where(function ($query) {
            collect(json_decode($this->request->get('intervals')))
                ->each(function ($interval, $table) use ($query) {
                    collect($interval)->each(function ($value, $column) use ($table, $query) {
                        $this->setMinLimit($table, $column, $value)
                            ->setMaxLimit($table, $column, $value);
                    });
                });
        });

        return $this;
    }

    private function setMinLimit($table, $column, $value)
    {
        if (is_null($value->min)) {
            return $this;
        }

        $min = property_exists($value, 'dbDateFormat')
            ? $this->formatDate($value->min, $value->dbDateFormat)
            : $value->min;

        $this->query->where($table . '.' . $column, '>=', $min);

        return $this;
    }

    private function setMaxLimit($table, $column, $value)
    {
        if (is_null($value->max)) {
            return $this;
        }

        $max = property_exists($value, 'dbDateFormat')
            ? $this->formatDate($value->max, $value->dbDateFormat)
            : $value->max;

        $this->query->where($table . '.' . $column, '<=', $max);

        return $this;
    }

    private function formatDate(string $date, string $dbDateFormat)
    {
        return (new Carbon($date))->format($dbDateFormat);
    }
}
