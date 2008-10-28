<?php
// $Id$

/**
 * 定义 QValidator_ValidateFailedException 异常
 */

/**
 * QValidator_ValidateFailedException 异常封装了验证失败事件
 */
class QValidator_ValidateFailedException extends QException
{
    /**
     * 被验证的数据
     *
     * @var array
     */
    public $validate_data;

    /**
     * 验证失败的结果
     *
     * @var array
     */
    public $validate_error;

    /**
     * 构造函数
     *
     * @param array $error
     * @param array $data
     */
    function __construct(array $error, array $data = array())
    {
        $this->validate_error = $error;
        $this->validate_data = $data;
        parent::__construct($this->formatToString());
    }

    /**
     * 格式化错误信息
     *
     * @param string $key
     *
     * @return string
     */
    function formatToString($key = null)
    {
        if (!is_null($key) && (isset($this->validate_error[$key])))
        {
            $error = $this->validate_error[$key];
        }
        else
        {
            $error = $this->validate_error;
        }

        $arr = array();
        foreach ($error as $messages)
        {
            if (is_array($messages))
            {
                $arr[] = implode(', ', $messages);
            }
            else
            {
                $arr[] = $messages;
            }
        }
        return implode('; ', $arr);
    }

    /**
     * 将异常转换为字符串
     *
     * @return string
     */
    function __toString()
    {
        return $this->formatToString();
    }
}

