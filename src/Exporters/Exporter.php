<?php

namespace RonasIT\Support\Exporters;

use Maatwebsite\Excel\Facades\Excel;
use RonasIT\Support\Iterators\DBIterator;

/**
 * @property DBIterator $iterator
 */
class Exporter
{
    protected $iterator;
    protected $fileName;
    protected $filters;
    protected $type = 'csv';
    protected $fields = [];

    public function setQuery($query)
    {
        $this->iterator = new DBIterator($query);

        return $this;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function setFilters($filters)
    {
        $this->filters = array_except($filters, ['token', 'export_type']);

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function export()
    {
        $info = Excel::create($this->getFileName(), function ($excel) {
            $excel->sheet('export', function ($sheet) {
                $this->exportFilters($sheet);

                $sheet->appendRow($this->fields);

                foreach ($this->iterator->getGenerator() as $line) {
                    $sheet->appendRow($this->getLine($line));
                }
            });
        })->store($this->type, false, true);

        return $info['full'];
    }

    private function getFileName()
    {
        return $this->fileName ?? uniqid();
    }

    public function getLine($item)
    {
        return array_map(function ($field) use ($item) {
            $value = array_get($item, $field);

            if (is_array($value)) {
                return json_encode($item[$field]);
            }

            return $value;
        }, $this->fields);
    }

    protected function exportFilters($sheet)
    {
        $sheet->appendRow(['Filters:']);

        foreach ($this->filters as $key => $value) {
            $line = [$key];

            if (is_array($value)) {
                $line = array_merge($line, $value);
            } else {
                $line[] = $value;
            }
            $sheet->appendRow($line);
        }

        $sheet->appendRow(['']);
    }
}