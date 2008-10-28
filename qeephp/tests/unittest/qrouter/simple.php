<?php
// $Id$

require_once dirname(__FILE__) . '/../../include/unittest_common.php';

/**
 * UnitTest_QRouter_Simple 测试简单路由规则的双向解析
 */
class UnitTest_QRouter_Simple extends QTest_UnitTest_Abstract
{
    /**
     * 用于测试的 URL 和期望的解析结果
     *
     * @var array
     */
    protected $_tests_url = array();

    /**
     * 用于测试反向解析的参数和期望的测试结果
     *
     * @var array
     */
    protected $_tests_args = array();

    protected function setUp()
    {
        $rules = Helper_YAML::load(FIXTURE_DIR . '/router/routes.yaml');
        foreach ($rules as $route_name => $rule)
        {
            $index = 0;
            foreach ($rule['tests_url'] as $test)
            {
                $this->_tests_url["{$route_name}-{$index}"] = $test;
                $index++;
            }
            unset($rules[$route_name]['tests_url']);

            $index = 0;
            foreach ($rule['tests_args'] as $test)
            {
                $this->_tests_args["{$route_name}:{$index}"] = $test;
                $index++;
            }
            unset($rules[$route_name]['tests_args']);
        }
        Q::setIni('routes', $rules);
    }

    /**
     * 测试对 URL 的分析
     */
    function testParse()
    {
        $router = new QRouter(QContext::instance());
        foreach ($this->_tests_url as $route_name => $test)
        {
            list($route_name) = explode('-', $route_name);
            $path = $test['_path'];
            unset($test['_path']);

            $result = $router->match($path);
            foreach ($test as $varname => $value)
            {
                if (!array_key_exists($varname, $result) || $value != $result[$varname])
                {
                    QDebug::dump($router->get($route_name), "route: {$route_name}");
                    QDebug::dump($result, 'parse result');
                }

                $this->assertArrayHasKey($varname, $result);
                $this->assertEquals($value, $result[$varname], 
                    sprintf('expected varname "%s" value is "%s".', $varname, $value));
                unset($result[$varname]);
            }

            $this->assertEmpty($result);
        }
    }

    /**
     * 测试反向解析
     */
    function testReservedParse()
    {
        $context = QContext::instance();
        $router = new QRouter($context);
        foreach ($this->_tests_args as $offset => $test)
        {
            $copy = $test;
            $path = $test['_path'];
            unset($test['_path']);

            $context->module_name = isset($test['module']) ? $test['module'] : null;
            $context->namespace = isset($test['namespace']) ? $test['namespace'] : null;
            $context->controller_name = isset($test['controller']) ? $test['controller'] : null;
            $context->action_name = isset($test['action']) ? $test['action'] : null;
            $context->setRequestUDI();

            $test['module'] = $context->module_name;
            $test['namespace'] = $context->namespace;
            $test['controller'] = $context->controller_name;
            $test['action'] = $context->action_name;

            $result = $router->url($test);
            list($route_name) = explode(':', $offset);

            if ($route_name != $router->reserve_matched_route_name)
            {
                QDebug::dump($test, "Expected route name is [{$offset}] : {$path}.");
            }

            $this->assertEquals($route_name, $router->reserve_matched_route_name, "Expected route name is [{$route_name}].");
            $this->assertEquals($path, $result, print_r($copy, true) . "\n" . print_r($test, true));
        }
    }

}


