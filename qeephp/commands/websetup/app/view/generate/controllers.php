<script language="javascript" type="text/javascript">
$(document).ready(function() {
	$("#controllers > ul").tabs();
});
</script>
<!-- BEGIN COL3 -->

<div id="col3_full">
  <div id="col3_content" class="clearfix">
    <!-- add your content here -->
    <div id="controllers">
      <ul>
        <li><a href="#tab_list"><span>现有的控制器</span></a></li>
        <li><a href="#tab_new"><span>创建新控制器</span></a></li>
      </ul>
    </div>
    <div id="tab_list">
      <table class="data full">
        <thead>
          <tr>
            <th width="180">控制器名称</th>
            <th>文件</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($controllers as $name => $file): ?>
          <tr>
            <th width="180"><?php echo h($name); ?></th>
            <td><?php echo h($file); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="tab_new">
      <form name="create_controller" action="<?php echo $this->context->url(null, 'newcontroller'); ?>" method="post">
        <table class="form-table full">
          <tr>
            <th valign="top"><label for="controller_name">控制器名称：</label></th>
            <td><input type="text" name="new_controller_name" id="new_controller_name" class="field" size="40" maxlength="30" />
              <br />
              控制起名称只能使用 26 个英文字母。还可以用 :: 来确定控制器所属的名字空间。<br />
              例如 admin::posts 表示 admin 名字空间里面的 posts 控制器。</td>
          </tr>
        </table>
        <p>
          <input type="submit" class="button" name="Submit" value="创建控制器" />
        </p>
      </form>
    </div>
  </div>
</div>
<!-- END COL3 -->
