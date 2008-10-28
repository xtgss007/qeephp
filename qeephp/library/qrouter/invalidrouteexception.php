<?php

class QRouter_InvalidRouteException extends QException
{
    public $route_name;
    public $rule;

    function __construct($route_name, $rule)
    {
        $this->route_name = $route_name;
        $this->rule = $rule;
        parent::__construct(__('Invalid route "%s".', $route_name));
    }
}


