<?php
/**
 * QDom_Element 类对PHP5自带的DOMElement进行了自己的扩展
 * QDom_Document和QDom_Element类对一些常见的dom操作进行了简化封装
 */
class QDom_Element extends DOMElement {
    /**
     * 魔法方法，获取attribute
     *
     * @param   string  $key
     * @return  mixed
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }

    /**
     * 魔法方法，设置attribute
     *
     * @param   string  $key
     * @param   string  $val
     */
    public function __set($key, $val) {
        $this->setAttribute($key, $val);
    }

    /**
     * 魔法方法，检查attribute是否存在
     *
     * @param   string  $key
     */
    public function __isset($key) {
        return $this->hasAttribute($key);
    }

    /**
     * 魔法方法，删除attribute
     *
     * @param   string  $key
     */
    public function __unset($key) {
        $this->removeAttribute($key);
    }

    /**
     * 返回当前element的xml字符串，相当于javascript dom里的outerHTML()
     *
     * @return  string
     */
    public function __toString() {
        return $this->ownerDocument->saveXML($this);
    }

    /**
     * 批量设置attribute
     *
     * @param   array   $attrs
     */
    public function setAttributes(array $attrs) {
        foreach ($attrs as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * 批量获取attribute，如果指定了key则只返回指定的
     *
     * @param   string  $key
     * @return  array
     */
    public function getAttributes(/* string */$key = null/* [, $key2[, $key3[, ...]]] */) {
        $result = array();
        if ($keys = func_get_args()) {
            foreach ($keys as $key) {
                $result[] = $this->$key;
            }
        } else {
            foreach ($this->attributes as $attr) {
                $result[$attr->nodeName] = $attr->nodeValue;
            }
        }
        return $result;
    }

    /**
     * xpath查询
     *
     * @param   string  $query
     * @param   boolean $return_first
     */
    public function select(/* string */$query, /* boolean */$return_first = false) {
        $result = $this->ownerDocument->xpath()->evaluate($query, $this);
        return ($return_first AND $result instanceof DOMNodelist) ? $result->item(0) : $result;
    }

    /**
     * 插入一个新的子节点到指定的子节点之后，返回插入的新子节点
     *
     * @param   DOMNode @newnode
     * @param   DOMNode @refnode
     * @return  DOMNode
     */
    public function insertAfter(DOMNode $newnode, DOMNode $refnode) {
        if ($refnode = $refnode->nextSibling) {
            $this->insertBefore($newnode, $refnode);
        } else {
            $this->appendChild($newnode);
        }
        return $newnode;
    }

    /**
     * 把节点插入到指定节点的指定位置
     *
     * @param   DOMNode $refnode
     * @param   string  $where
     * @return  DOMNode
     */
    public function inject(DOMNode $refnode, $where = 'bottom') {
        $where = strtolower($where);

        if ('before' == $where) {
            $refnode->parentNode->insertBefore($this, $refnode);
        } elseif ('after' == $where) {
            $refnode->parentNode->insertAfter($this, $refnode);
        } else {
            if ('top' == $where AND $first = $refnode->firstChild) {
                $refnode->insertBefore($this, $first);
            } else {
                $refnode->appendChild($this);
            }
        }

        return $this;
    }

    /**
     * 是否是第一个子节点
     *
     * @return  boolean
     */
    public function isFirst() {
        return $this->prefiousSibling ? false : true;
    }

    /**
     * 是否最后一个子节点
     *
     * @return  boolean
     */
    public function isLast() {
        return $this->nextSibling ? false : true;
    }

    /**
     * 清除所有的子节点
     *
     * @return DOMNode
     */
    public function empty() {
        foreach ($this->childNodes as $child) {
            $this->removeChild($child);
        }
        return $this;
    }

    /**
     * 删除自己
     */
    public function erase() {
        $this->parentNode->removeChild($this);
    }
}
