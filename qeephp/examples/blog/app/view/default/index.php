<?php foreach ($posts as $post_offset => $post): ?>

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

<?php endforeach; ?>

<p>&nbsp;</p>
