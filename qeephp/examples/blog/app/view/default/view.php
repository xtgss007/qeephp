<h3><a href="<?php echo $this->context->url(null, 'view', array('id' => $post->post_id)); ?>"><?php echo h($post->title); ?></a></h3>

<p class="created">
  <?php echo date('Y年m月d日 H:i', $post->created); ?>
  <a href="<?php echo $this->context->url(null, 'edit', array('id' => $post->post_id)); ?>">编辑</a>
  <a href="<?php echo $this->context->url(null, 'delete', array('id' => $post->post_id)); ?>" onclick="return confirm('您确定要删除该篇文章？');">删除</a>
</p>

<div class="body">
  <?php echo $post->formatted_body; ?>
</div>

<p class="meta">

  标签:
  <?php foreach ($post->tags as $tag): ?>
  <a href="<?php echo $this->context->url(null, null, array('tag' => $tag->slug)); ?>" title="查看指定了该标签的所有文章"><?php echo h($tag->label); ?></a>
  &nbsp;
  <?php endforeach; ?>

  <span>|</span>
  <a href="<?php echo $this->context->url(null, 'view', array('id' => $post->post_id)); ?>">评论(<?php echo $post->comments_count; ?>)</a>

</p>

<hr />

<div id="comments">

  <h3>网友评论：</h3>

<?php if (count($post->comments)): ?>

  <?php foreach ($post->comments as $offset => $comment): ?>

  <p class="created">

    #<?php echo $offset + 1; ?> <?php echo date('Y年m月d日 H:i', $comment->created); ?>

    <a href="<?php echo $this->context->url(null, 'delete_comment', array('id' => $comment->comment_id)); ?>" onclick="return confirm('您确定要删除该评论？');">删除</a>

  </p>

  <div class="comment_body">
    <?php echo $comment->formatted_body; ?>
  </div>

  <?php endforeach; ?>

<?php else: ?>

  <p>没有评论。</p>

<?php endif; ?>

  <hr />

  <form id="form1" method="post" action="<?php echo $this->context->url(null, 'comment'); ?>"
        onsubmit="if (this.body.value == '') { alert('请输入评论内容'); return false; } else { return true; }">
    <label>添加评论内容：</label>
    <span class="created">可以使用 BBCode 格式化内容</span>
    <br />
    <textarea name="body" rows="12" class="textbox"></textarea>
    <br />
    <br />
    <input type="submit" name="Submit" value="添加评论" />
    <?php $this->html->hidden('post_id', $post->post_id); ?>
  </form>

</div>

<p>&nbsp;</p>
