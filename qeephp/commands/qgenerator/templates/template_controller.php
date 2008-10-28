<?php echo '<?php'; ?>

// $Id$

/**
 * <?php echo $class_name; ?> 控制器
 */
class <?php echo $class_name; ?> extends AppController_Abstract
{
	/**
	 * 默认动作
	 */
	function actionIndex()
	{
		/**
		 * 要传递到视图的数据，可以直接赋值给 $this->view。
		 *
		 * 为了便于在视图中使用这些数据，$this->view 应该是一个数组，键名对应视图中的变量名。
		 */
		# $this->view['text'] = 'Hello!';
	}
}
