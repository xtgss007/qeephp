<?php
/**
 * QDom_Document 类对PHP5自带的DOMDocument进行了自己的扩展
 * QDom_Document和QDom_Element类对一些常见的dom操作进行了简化封装
 */
class QDom_Document extends DOMDocument {
    /**
     * 构造函数
     *
     * @param   string  $version
     * @param   string  $encoding
     */
    public function __construct(/* string */$version = '1.0', /* string */$encoding = 'utf-8') {
        parent::__construct($version, $encoding);
        // 把QDom_Element类注册为默认的Node class
        $this->registerNodeClass('DOMElement', 'QDom_Element');
    }

    /**
     * xpath查询
     *
     * @param   string  $query
     * @param   boolean $return_first
     * @return  mixed
     */
    public function select(/* string */$query, /* boolean */$return_first = false) {
        $result = $this->xpath()->evaluate($query, $this);
        return ($return_first AND $result instanceof DOMNodelist) ? $result->item(0) : $result;
    }

    /**
     * 生成当前document的xpath查询handle
     *
     * @return  DOMXPath
     */
    public function xpath() {
        static $xpath = null;
        if (null === $xpath) {
            $xpath = new DOMXPath($this);
        }
        return $xpath;
    }

    /**
     * 返回当前document的xml字符串内容
     *
     * @return  string
     */
    public function __toString() {
        return $this->saveXML();
    }
}
