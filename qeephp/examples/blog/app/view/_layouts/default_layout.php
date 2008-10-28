<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>我的博客</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="all">

  <div id="header">
    <h1>我的博客</h1>
  </div>

  <div id="nav">
    <?php $this->control->make('nav', 'nav'); ?>
  </div>

  <div id="content">

    <div id="main">

    <?php if (!empty($flash_message)): ?>
    <div id="flash_message">
    <?php echo nl2br(h($flash_message)); ?>
    </div>
    <?php endif; ?>

    <?php echo $contents_for_layouts; ?>

    </div>

    <div id="sidebar">
      <?php $this->control->make('sidebar', 'sidebar'); ?>
    </div>

    <div class="nofloat"></div>

  </div>

  <div id="footer">
    Powered by <a href="http://qeephp.org" target="_blank">QeePHP <?php echo Q_VERSION; ?></a>
  </div>

</div>
</body>
</html>
