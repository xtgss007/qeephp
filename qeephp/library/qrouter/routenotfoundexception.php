<?php

class QRouter_RouteNotFoundException extends QException
{
    public $route_name;

    function __construct($route_name)
    {
        $this->route_name = $route_name;
        parent::__construct(__('Route "%s" not found.', $route_name));
    }
}

