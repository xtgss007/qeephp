
<div id="tags_cloud">
  <h3>标签云：</h3>

  <?php foreach ($tags as $tag): ?>
  <a href="<?php echo $this->context->url(null, null, array('tag' => $tag->slug)); ?>"><?php echo h($tag->label); ?></a>
  &nbsp;
  <?php endforeach; ?>
</div>

<div id="recent_comments">
  <h3>最近评论：</h3>
  
  <?php foreach ($comments as $comment): ?>
  <a href="<?php echo $this->context->url(null, 'view', array('id' => $comment->post_id)); ?>"><?php echo date('Y年m月d日 H:i', $comment->created); ?></a>
  
  <div class="comment_body">
    <?php echo h(mb_strimwidth(strip_tags($comment->body), 0, 100, '...', 'utf8')); ?>
  </div>
  
  <?php endforeach; ?>
</div>
