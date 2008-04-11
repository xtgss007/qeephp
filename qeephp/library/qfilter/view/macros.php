<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QFilter_View_Macros 类
 *
 * @package filter
 * @version $Id$
 */

/**
 * QFilter_View_Macros 将视图中的宏替换为运行时值
 *
 * @package filter
 */
class QFilter_View_Macros
{
    /**
     * 要搜索的宏
     *
     * @var array
     */
    protected $search = array(
        '%MACRO:PUBLIC_ROOT%',
        '%MACRO:BASE_URI%',
        '%MACRO:REQUEST_URI%',
    );

    /**
     * 宏的替换值
     *
     * @var array
     */
    protected $replace = array();

    /**
     * 请求对象
     *
     * @var QRequest
     */
    protected $request;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->request = Q::getSingleton(Q::getIni('dispatcher_request_class'));
        $this->replace[] = $this->request->getBaseDir();
        $this->replace[] = $this->request->getBaseUri();
        $this->replace[] = $this->request->getRequestUri();
    }

    /**
     * 对特定内容应用过滤器
     *
     * @param string $content
     *
     * @return string
     */
    function apply($content)
    {
        return str_replace($this->search, $this->replace, $content);
    }
}
