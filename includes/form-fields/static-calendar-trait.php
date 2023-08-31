<?php


namespace JET_APB\Form_Fields;


trait Static_Calendar_Trait {

	public function render_static_calendar() {

		ob_start();
		include JET_APB_PATH . 'templates/public/static-calendar.php';

		echo '<style>';
			include JET_APB_PATH . 'assets/css/public/vanilla-calendar.css';
		echo '</style>';
		return ob_get_clean();

	}

}