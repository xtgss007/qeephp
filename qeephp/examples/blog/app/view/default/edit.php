
<!-- 渲染表单 -->
<?php /* @var $form QForm */ $form->renderWithLayoutClass('QForm_Layout_Nested', $_ctx); ?>


<!-- 列出日志的评论 -->
<?php if (isset($post)): ?>

<hr />
<div id="comments">

  <h3>网友评论：</h3>

<?php if (count($post->comments)): ?>

  <?php foreach ($post->comments as $offset => $comment): ?>

  <p class="created">#<?php echo $offset + 1; ?> <?php echo date('Y年m月d日 H:i', $comment->created); ?></p>

  <div class="comment_body">
    <?php echo $comment->formatted_body; ?>
  </div>

  <?php endforeach; ?>

<?php else: ?>

  <p>没有评论。</p>

<?php endif; ?>

</div>

<?php endif; ?>

<p>&nbsp;</p>
