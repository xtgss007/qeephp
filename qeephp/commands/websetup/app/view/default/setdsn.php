<script language="javascript" type="text/javascript">
$(document).ready(function() {
	$("#dsn > ul").tabs();
});

</script>
<!-- BEGIN COL3 -->

<div id="col3_full" class="clear_right_margin">
  <div id="col3_content" class="clearfix">
    <!-- add your content here -->
    <div id="dsn">
      <ul>
        <?php foreach (array_keys($db_dsn_pool) as $section): ?>
        <li><a href="#tab_<?php echo $section; ?>"><span><?php echo h($section); ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  
    <form name="update_dsn" id="update_dsn" action="<?php $this->url('/updateDSN'); ?>" method="post">

    <?php foreach ($db_dsn_pool as $section => $dsn): ?>
    <div id="tab_<?php echo $section; ?>">
        <table class="data full">
          <thead>
            <tr>
              <th width="180">项目</th>
              <th>当前值</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dsn as $item => $value): ?>
            <tr>
              <th width="180"><?php echo h($item); ?></th>
              <td><input name="<?php echo $section; ?>_dsn_<?php echo $item; ?>" value="<?php echo h($value); ?>" class="field" /></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <input type="submit" name="button_save" id="button_save" value="保存所有修改" class="button" />
    </form>

  </div>
</div>
<!-- END COL3 -->
