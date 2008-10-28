<div id="page">
  <div id="sidebar">
	<ul id="sidebar-items">
	  <li>
		<form id="search" action="http://www.google.com/search" method="get" target="_blank">
		  <input type="hidden" name="hl" value="en" />
		  <input type="text" id="search-text" name="q" value="site:qeephp.org " />
		  <input type="submit" value="搜索" />
		  QeePHP 网站
		</form>
	  </li>
	  <li>
		<h3>加入社区</h3>
		<ul class="links">
		  <li><a href="http://www.fleaphp.org/">QeePHP</a></li>
		  <li><a href="http://www.fleaphp.org/bbs/">论坛</a></li>
		  <li><a href="http://code.google.com/p/qeephp/issues/list">Bug 报告</a></li>
		</ul>
	  </li>
	  <li>
		<h3>浏览文档</h3>
		<ul class="links">
		  <li><a href="http://www.qeephp.org/docs/api/index.html">QeePHP API 文档</a></li>
		  <li><a href="http://www.php.net/docs.php">PHP 文档</a></li>
		</ul>
	  </li>
	</ul>
  </div>
  <div id="content">
	<div id="header">
	  <h1>开始我的 QeePHP 之旅</h1>
	  <h2>驾驭 QeePHP 从这里开始！&nbsp; <?php echo h($text); ?></h2>
	</div>
	<div id="getting-started">
	  <h1>快速开始</h1>
	  <h2>如何开始我的应用程序：</h2>
	  <ol>
		<li>
		  <h2>建立数据库，并且修改 <tt>config/database.yaml</tt> 文件</h2>
		  <p>QeePHP 需要知道如何连接数据库。</p>
		</li>
		<li>
		  <h2>使用 <tt>php script/generate.php</tt> 来自动创建控制器、模型以及表数据入口</h2>
		  <p>要查看 generate.php 可用的选项，不带参数执行 php script/generate.php 即可。</p>
		</li>
		<li>
		  <h2>修改应用程序设置 <tt>config/environment.yaml</tt></h2>
		  <p>这个文件控制了应用程序和 QeePHP 框架的行为。</p>
		</li>
		<li>
		  <h2>修改应用程序启动脚本 <tt>config/boot.php</tt></h2>
		  <p>修改启动脚本确保在正确的位置载入 QeePHP 框架。</p>
		</li>
	  </ol>
	</div>
  </div>
  <div id="footer">&nbsp;</div>
</div>
