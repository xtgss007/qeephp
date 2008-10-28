<?php
// $Id$


class QCallback implements ArrayAccess
{
    protected $_callbacks = array();

    function call($method_name, array $args)
    {
        $callback = $this->get($method_name);
        return call_user_func_array($callback, $args);
    }

    function get($method_name)
    {
        if (isset($this->_callbacks[$method_name]))
        {
            return $this->_callbacks[$method_name];
        }

        throw new QCallback_Exception(__('Method "%s" not found.', $method_name));
    }

    function set($method_name, $callback)
    {
        $this->_callbacks[$method_name] = $callback;
        return $this;
    }

    function remove($method_name)
    {
        unset($this->_callbacks[$method_name]);
        return $this;
    }

    function exists($method_name)
    {
        return isset($this->_callbacks[$method_name]);
    }

    function offsetExists($method_name)
    {
        return $this->exists($method_name);
    }

    function offsetGet($method_name)
    {
        return $this->get($method_name);
    }

    function offsetSet($method_name, $callback)
    {
        $this->set($method_name, $callback);
    }

    function offsetUnset($method_name)
    {
        $this->remove($method_name);
    }

}

