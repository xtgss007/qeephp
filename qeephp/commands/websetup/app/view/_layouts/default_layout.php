<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>WebSetup for QeePHP</title>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<link href="<?php $this->url('static/css'); ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" language="javascript" src="<?php $this->url('static/js'); ?>"></script>
</head>
<body>
<div id="page_margins">

  <!-- BEGIN PAGE -->

  <div id="page">

    <!-- BEGIN HEADER -->

    <div id="header">
      <div id="topnav">
        <a href="<?php echo dirname($_ctx->getBaseDir()); ?>/public/" target="_blank">访问我的应用程序</a>
      </div>
      <h1>WebSetup for QeePHP</h1>
    </div>

    <!-- END HEADER -->

	<!-- BEGIN NAV -->

    <div id="nav">
      <!-- skiplink anchor: navigation -->
      <a id="navigation" name="navigation"></a>
      <div id="nav_main">
        <?php $this->control->make('navmain', 'navmain'); ?>
      </div>
    </div>

    <?php if (!empty($g['flash_message'])): ?>
    <div id="teaser">
    <p class="<?php if ($g['flash_message_type'] == QApplication_Abstract::FLASH_MSG_ERROR || $g['flash_message_type'] == QApplication_Abstract::FLASH_MSG_WARNING): echo 'warning'; else: echo 'note'; endif; ?>">
        <span class="float_right"><a href="#" onclick="$('#teaser').hide(); return false;">隐藏该信息</a></span>

        <?php echo nl2br(h($g['flash_message'])); ?>
      </p>
    </div>
    <?php endif; ?>

    <!-- END NAV -->

    <!-- BEGIN MAIN -->

    <div id="main">

      <!-- BEGIN COL1 -->

      <div id="col1">
        <div id="col1_content" class="clearfix">
          <!-- add your content here -->
          <?php $this->control->make('submenu', 'submenu'); ?>
          <p id="help_text" class="note"><?php echo nl2br(h($g['help_text'])); ?></p>
        </div>
      </div>

      <!-- END COL1 -->

      <!-- BEGIN CONTENTS_FOR_LAYOUTS -->

      <?php echo $contents_for_layouts; ?>

      <!-- END CONTENTS_FOR_LAYOUTS -->

      <!-- IE Column Clearing -->
      <div id="ie_clearing"> &#160; </div>
    </div>

    <!-- END MAIN -->

  </div>

  <!-- END PAGE -->

  <!-- BEGIN FOOTER -->

  <div id="footer">
    <p>
      WebSetup for <a href="http://www.qeephp.org/" target="_blank">QeePHP</a> |
      Layout based on <a href="http://www.yaml.de/" target="_blank">YAML</a> |
      运行时间：<span id="runtime_info_elapsed_time"></span> |
      特别感谢 <a href="http://www.fleaphp.org/bbs/viewthread.php?tid=3266" target="_blank">dos2000 创建的 WebSetup for FleaPHP 应用</a>
    </p>
  </div>

  <!-- END FOOTER -->

</div>
</body>
</html>

