<a href="<?php echo $this->context->url(); ?>"<?php if ($current_location == 'index' && !$current_tag): ?> class="current"<?php endif; ?>>全部文章</a>

<?php if ($current_tag): ?>
  <a href="<?php echo $this->context->url(null, null, array('tag' => $current_tag->slug)); ?>" class="current">分类[<?php echo h($current_tag->label); ?>]的文章列表</a>
<?php endif; ?>

<?php if ($current_post): ?>
  <a href="<?php echo $this->context->url(null, 'view', array('id' => $current_post->post_id)); ?>" class="current"><?php echo h($current_post->title); ?></a>
<?php endif; ?>

<a href="<?php echo $this->context->url(null, 'edit'); ?>"<?php if ($current_location == 'edit' || $current_location == 'add'): ?> class="current"<?php endif; ?>><?php if ($current_location == 'edit'): ?>编辑文章<?php else: ?>写新文章<?php endif; ?></a>
