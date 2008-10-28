<ul>
<?php foreach ($all_menu as $menu): ?>
  <li<?php if ($current['controller'] == $menu['controller']): ?> id="current"<?php endif; ?>>
    <a href="<?php $this->url($menu['controller'], $menu['action']); ?>"><?php echo h($menu['title']); ?></a>
  </li>
<?php endforeach; ?>
</ul>
