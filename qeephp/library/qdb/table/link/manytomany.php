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
 * 定义 QDB_Table_Link_ManyToMany 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Link_ManyToMany 类封装数据表之间的 many to many 关联
 *
 * @package database
 */
class QDB_Table_Link_ManyToMany extends QDB_Table_Link_Abstract
{
    /**
     * 构造函数
     *
     * @param string $name
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Table_Link
     */
    function __construct($name, array $params, QDB_Table $source_table)
    {
        parent::__construct($name, self::many_to_many, $params, $source_table);
        $this->one_to_one = false;

        /**
         * mid_table_obj、mid_table_class、mid_table_name 三者只需要指定一个，三者的优先级从上到下。
         * 如果 mid_table_name 有效，则可以通过 mid_table_params 指示构造关联表数据入口时的选项。
         *
         */
        if (!empty($params['mid_table_obj'])) {
            $this->mid_table = $params['mid_table_obj'];
        } elseif (!empty($params['mid_table_class'])) {
            $this->mid_table = Q::getSingleton($params['mid_table_class']);
        } else {
            $mid_table_params = !empty($params['mid_table_params']) ? (array)$params['mid_table_params'] : array();
            if (empty($params['mid_table_name']) && empty($mid_table_params['table_name'])) {
                // 尝试自动设置中间表名称
                $t1 = $this->source_table->table_name;
                $t2 = $this->target_table->table_name;
                if ($t1 <= $t2) {
                    $mid_table_params['table_name'] = $t1 . '_has_' . $t2;
                } else {
                    $mid_table_params['table_name'] = $t2 . '_has_' . $t2;
                }
            }
            $this->mid_table = new QDB_Table($mid_table_params);
        }
        $this->mid_table->connect();

        $this->main_key         = !empty($params['main_key']) ? $params['main_key'] : $this->source_table->pk;
        $this->target_key       = !empty($params['target_key']) ? $params['target_key'] : $this->target_table->pk;
        $this->mid_main_key     = !empty($params['mid_main_key']) ? $params['mid_main_key'] : $this->source_table->pk;
        $this->mid_target_key   = !empty($params['mid_target_key']) ? $params['mid_target_key'] : $this->target_table->pk;
        $this->mid_on_find_keys = !empty($params['mid_on_find_keys']) ? $params['mid_on_find_keys'] : null;
        $this->mid_mapping_to   = !empty($params['mid_mapping_to']) ? $params['mid_mapping_to'] : 'mid_data';
        $this->on_delete        = 'skip';
        $this->on_save          = !empty($params['on_save']) ? $params['on_save']       : 'skip';
    }

    /**
     * 保存多对多记录
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTargetData(array $target_data, $source_key_value, $recursion)
    {
        /**
         * 算法：
         *
         * 1、取出中间表中已有的关联关系
         * 2、和应用程序提供的关联关系进行比对
         * 3、确定要添加的关系
         * 4、确定要删除的关系
         */
        $mid_rowset = array();
        $target_key_values = array();

        $keys = array_keys($target_data);
        foreach ($keys as $offset) {
            // 如果目标数据不是一个数组，则假定为目标的 target_key 值
            if (!is_array($target_data[$offset])) {
                $target_key_values[$offset] = $target_data[$offset];
                continue;
            }

            // 分离出中间表字段
            if (!empty($target_data[$offset][$this->mid_mapping_to])) {
                $mid_rowset[$offset] = $target_data[$offset][$this->mid_mapping_to];
                unset($target_data[$offset][$this->mid_mapping_to]);
            }

            // 确定目标数据
            if (!empty($target_data[$offset][$this->target_key])) {
                $target_key_values[$offset] = $target_data[$offset][$this->target_key];
            } else {
                // 如果关联记录尚未保存到数据库，则创建一条新的关联记录
                $target_key_values[$offset] = $this->target_table->create($target_data[$offset], $recursion);
            }
        }

        // 取出现有的关联信息
        $conn = $this->mid_table->getConn();
        $sql = sprintf('SELECT %s FROM %s WHERE %s = %s',
                       $conn->qfield($this->mid_target_key),
                       $this->mid_table->qtable_name,
                       $conn->qfield($this->mid_source_key),
                       $conn->qstr($source_key_value)
        );
        $exists_mid = $conn->getCol($sql);

        // 然后确定要添加的关联信息
        $insert_mid = array_flip(array_diff($target_key_values, $exists_mid));
        $remove_mid = array_flip(array_diff($exists_mid, $target_key_values));
        $exists_mid = array_flip($exists_mid);

        foreach ($keys as $offset) {
            if (isset($insert_mid[$target_key_values[$offset]])) {
                // 增加一个中间记录
                if (!empty($mid_rowset[$offset])) {
                    $row = $mid_rowset[$offset];
                } else {
                    $row = array();
                }
                $row[$this->mid_source_key] = $source_key_value;
                $row[$this->mid_target_key] = $target_key_values[$offset];
                $this->mid_table->create($row);
            } elseif (isset($remove_mid[$target_key_values[$offset]])) {
                // 删除一个中间记录
                $this->mid_table->remove($target_key_values[$offset]);
            } elseif (isset($exists_mid[$target_key_values[$offset]])) {
                // 更新一个中间记录
                if (empty($mid_rowset[$offset])) { continue; }
                $row = $mid_rowset[$offset];
                $row[$this->mid_source_key] = $source_key_value;
                $row[$this->mid_target_key] = $target_key_values[$offset];
                $this->mid_table->update($row);
            }
        }
    }

    /**
     * 删除目标数据
     *
     * @param mixed $target_key_value
     * @param int $recursion
     */
    function removeTargetData($source_key_value, $recursion)
    {
        // 必须删除中间表里面，来源数据与目标数据的关联
        $this->mid_table->removeByField($this->mid_source_key, $source_key_value, $recursion);
    }
}
