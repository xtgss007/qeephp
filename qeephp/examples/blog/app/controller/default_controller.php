<?php

/**
 * 默认控制器
 */
class Controller_Default extends AppController_Abstract
{
    /**
     * 默认页面
     */
    function actionIndex()
    {
        if ($this->context->tag)
        {
            $tag = Tag::find('label = ?', rawurldecode($this->context->tag))->query();
            if ($tag->id())
            {
                $this->view['current_tag'] = $tag;
                $this->view['posts'] = $tag->posts;
                $this->context->setParam('current_tag', $tag);
            }
        }

        if (!isset($this->view['posts']))
        {
            $this->view['posts'] = Post::find()->order('created DESC')->all()->query();
        }

        $this->context->setParam('current_location', 'index');
    }

    /**
     * 添加和编辑界面
     *
     * @param QForm $form
     */
    function actionEdit(QForm $form = null)
    {
        if (is_null($form))
        {
            $post = Post::find('post_id = ?', $this->context->id)->query();
            $this->view['post'] = $post;

            $form = new PostForm($this->_url('save', array('id' => $this->context->id)));
            $form->setValue($post);
        }

        $this->view['form'] = $form;

        if ($this->context->id)
        {
        	$this->context->setParam('current_location', 'edit');
        }
        else
        {
        	$this->context->setParam('current_location', 'add');
        }
    }

    /**
     * 保存修改
     */
    function actionSave()
    {
        $form = new PostForm($this->_url('save', array('id' => $this->context->id)));

        try
        {
            $data = $form->setValue($this->context)->getValue();
            if ($this->context->id)
            {
                $post = Post::find('post_id = ?', $this->context->id)->query();
            }
            else
            {
                $post = new Post();
            }
            $post->setProps($data);
            $post->save();
        }
        catch (QValidator_ValidateFailedException $ex)
        {
            $this->app->setFlashMessage('下列数据验证失败：' . $ex->__toString());
            return $this->_forward('/edit', $form);
        }

        $this->app->setFlashMessage('保存成功');
        return $this->_redirect($this->_url());
    }

    /**
     * 删除
     */
    function actionDelete()
    {
    	$c = Post::meta()->destroyWhere('post_id = ?', $this->context->id);
    	$this->app->setFlashMessage("删除了 {$c} 个文章");
    	return $this->_redirect($this->_url());
    }

    /**
     * 查看页面
     */
    function actionView()
    {
    	$this->view['post'] = Post::find('post_id = ?', $this->context->id)->query();
    	$this->context->setParam('current_location', 'view');
    	$this->context->setParam('current_post', $this->view['post']);
    }

    /**
     * 添加评论
     */
    function actionComment()
    {
    	$post = Post::find('post_id = ?', $this->context->post_id)->query();
    	$body = trim($this->context->body);
    	if ($post->id() && !empty($body))
    	{
    		$comment = new Comment(array('body' => $body));
    		$post->addComment($comment);
    		$this->app->setFlashMessage('您的评论已经保存');
    		return $this->_redirect($this->_url('view', array('id' => $post->post_id)));
    	}
    	else
    	{
    		$this->app->setFlashMessage('非法的参数');
    		return $this->_redirect($this->_url());
    	}
    }

    /**
     * 删除评论
     */
    function actionDeleteComment()
    {
    	$comment = Comment::find('comment_id = ?', $this->context->id)->query();
    	if ($comment->id())
    	{
    		$comment->destroy();
    		$this->app->setFlashMessage('删除了 1 个评论');
            return $this->_redirect($this->_url('view', array('id' => $comment->post_id)));
    	}
    	else
    	{
            $this->app->setFlashMessage('非法的参数');
            return $this->_redirect($this->_url());
    	}
    }
}
