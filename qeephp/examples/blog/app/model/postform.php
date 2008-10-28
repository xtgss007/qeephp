<?php

class PostForm extends QForm
{

    function __construct($action)
    {
        parent::__construct('post_form', $action);

        // 输入框
        $props = array('class' => 'textbox', 'max_length' => 85);
        $this->add(new QForm_Element('title', 'textbox', $props))
             ->set('label', '文章标题')
             ->set('description', '');

        $props = array('class' => 'textbox', 'rows' => 20);
        $this->add(new QForm_Element('body', 'memo', $props))
             ->set('label', '文章内容')
             ->set('description', '可以使用 BBCode 格式化内容');

        $props = array('class' => 'textbox', 'max_length' => 60);
        $this->add(new QForm_Element('tags', 'textbox', $props))
             ->set('label', '标签')
             ->set('description', '多个类别间请用空格分隔')
             ->onSetValue(array($this, 'setTags'));

        $props = array('caption' => '保存修改');
        $this->add(new QForm_Element('save', 'submit', $props));

        $this->loadValidationsFromModel('Post');
    }

    function setTags($tags)
    {
        if (is_array($tags) || $tags instanceof Iterator)
        {
            $arr = array();
            foreach ($tags as $tag)
            {
                $arr[] = $tag->label;
            }
            $tags = $arr;
        }

        return implode(' ', Q::normalize($tags, ' '));
    }

}

