
<script language="javascript" type="text/javascript">
$(document).ready(function() {
	$("#appinfo > ul").tabs();
});
</script>

<?php

$cat = array
(
    'app_config'    => '应用程序基本配置',
    'ini'           => '应用程序设置',
    'acl_data'      => '访问控制信息',
);

$cat_arr = array
(
    'app_config'    => $app_config, 
    'ini'           => $all_ini,
    'acl_data'      => $all_ini['acl_global_act'],
);

unset($cat_arr['ini']['acl_global_act']);
unset($cat_arr['ini']['db_dsn_pool']);
unset($cat_arr['ini']['routes']);

?>

<!-- BEGIN COL3 -->
<div id="col3_full">
  <div id="col3_content" class="clearfix">
    <!-- add your content here -->
    <div id="appinfo">
      <ul>

        <?php foreach ($cat as $section => $title): ?>

        <li><a href="#tab_<?php echo $section; ?>"><span><?php echo h($title); ?></span></a></li>

        <?php endforeach; ?>

      </ul>
    </div>


    <?php foreach ($cat as $section => $title): $data = $cat_arr[$section]; ?>

    <div id="tab_<?php echo $section; ?>">
      <table class="data full">
        <thead>
          <tr>
            <th width="180">项目</th>
            <th>当前值</th>
            <th>备注</th>
          </tr>
        </thead>
        <tbody>

          <?php foreach ((array)$data as $key => $value): ?>

          <tr>
            <th width="180"><?php echo h($key); ?></th>
            <td><?php echo Helper_Ini::value($value); ?></td>
            <td><?php $descr = isset($ini_descriptions[$key]) ? $ini_descriptions[$key] : '-'; echo nl2br(h($descr)); ?></td>
          </tr>

          <?php endforeach; ?>

        </tbody>
      </table>
    </div>

    <?php endforeach; ?>

  </div>
</div>
<!-- END COL3 -->

