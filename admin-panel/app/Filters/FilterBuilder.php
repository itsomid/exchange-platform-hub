<?php

namespace App\Filters;

use Illuminate\Support\Str;

class FilterBuilder
{
    protected $query;

    protected $filters;

    protected $namespace;

    public function __construct($query, $filters, $namespace)
    {
        $this->query = $query;
        $this->filters = $filters;
        $this->namespace = $namespace;
    }

    public function apply()
    {
        foreach ($this->filters as $name => $value) {

            $name = Str::camel($name);
            $name = ucfirst($name);

            $class = $this->namespace."\\{$name}";

            if (! class_exists($class)) {
                continue;
            }

            $instance = new $class($this->query);

            if ((is_string($value) && strlen($value)) || (is_array($value) && count($value))) {
                $instance->handle($value);
            } else {
                $instance->handle();
            }
        }

        return $this->query;
    }
}
