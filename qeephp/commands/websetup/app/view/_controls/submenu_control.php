
<ul id="submenu">
  <li id="title"><?php echo $main['title']; ?></li>
  <?php foreach ($main['items'] as $item): ?>
  <li<?php if ($menu->compare($item, $current)): ?> id="active"<?php endif; ?>>
    <a href="<?php $this->url($item['controller'], $item['action'], null, $item['namespace']); ?>"><?php echo h($item['title']); ?></a>
  </li>
  <?php endforeach; ?>
</ul>
