<?php
namespace JET_APB\Admin\Helpers;

/**
 * Common component define
 */
class Common_Component {

	private $name = null;
	private $prefix = null;
	private $path = null;

	/**
	 * Setup props
	 */
	public function __construct( $name = null, $prefix = null, $template_path = null, $file_path = null ) {
		$this->name          = $name;
		$this->prefix        = ! empty( $prefix ) ? $prefix : 'jet-apb-';
		$this->template_path = ! empty( $template_path ) ? $template_path : 'admin/common/';
		$this->file_path     = ! empty( $file_path ) ? $file_path : 'admin/';
	}

	/**
	 * Enqueue component assets
	 */
	public function assets() {
		wp_enqueue_script(
			$this->prefix . $this->name,
			JET_APB_URL . 'assets/js/' . $this->file_path . $this->name . '.js',
			array(),
			JET_APB_VERSION,
			true
		);
	}

	/**
	 * Get cofig prop
	 *
	 * @return [type] [description]
	 */
	public function template() {

		ob_start();
		include JET_APB_PATH .'templates/' . $this->template_path . $this->name . '.php';
		$content = ob_get_clean();

		printf(
			'<script type="text/x-template" id="%2$s%1$s">%3$s</script>',
			$this->name,
			$this->prefix,
			$content
		);
	}

}
