<?php
namespace JET_APB\Workflows\Actions;

use JET_APB\Workflows\Base_Object;
use JET_APB\Plugin;

class Send_Email extends Base_Action {

	public function get_id() {
		return 'send-email';
	}

	public function register_action_controls() {
		echo '
		<cx-vui-input
			label="' . __( 'Email to', 'jet-appointments-booking' ) . '"
			description="' . __( 'Address to send email to. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'send-email\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].email_to"
			@on-input-change="setActionProp( actionIndex, \'email_to\', $event.target.value )"
			ref="email_to"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'email_to\', $event )"/></cx-vui-input>
		<cx-vui-input
			label="' . __( 'Email subject', 'jet-appointments-booking' ) . '"
			description="' . __( 'Subject of email to sent. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'send-email\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].email_subject"
			@on-input-change="setActionProp( actionIndex, \'email_subject\', $event.target.value )"
			ref="email_subject"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'email_subject\', $event )"/></cx-vui-input>
		<cx-vui-input
			label="' . __( 'Sent from email', 'jet-appointments-booking' ) . '"
			description="' . __( 'Email address to set as `From address`. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'send-email\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].email_from"
			@on-input-change="setActionProp( actionIndex, \'email_from\', $event.target.value )"
			ref="email_from"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'email_from\', $event )"/></cx-vui-input>
		<cx-vui-input
			label="' . __( 'Sent from name', 'jet-appointments-booking' ) . '"
			description="' . __( 'Name to set as `From name`. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'send-email\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].email_from_name"
			@on-input-change="setActionProp( actionIndex, \'email_from_name\', $event.target.value )"
			ref="email_from_name"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'email_from_name\', $event )"/></cx-vui-input>
		<cx-vui-textarea
			label="' . __( 'Email message', 'jet-appointments-booking' ) . '"
			description="' . __( 'Content of email to sent. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'send-email\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].email_message"
			rows="6"
			@on-input-change="setActionProp( actionIndex, \'email_message\', $event.target.value )"
			ref="email_message"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'email_message\', $event )"/></cx-vui-textarea>
		';
	}

	public function get_name() {
		return __( 'Send Email', 'jet-appointments-bookin' );
	}
	
	public function do_action( $data = [] ) {

		$this->fetch_appointments_meta();
		
		$email_to = $this->parse_macros( $this->get_settings( 'email_to' ) );
		$email_subject = $this->parse_macros( $this->get_settings( 'email_subject' ) );
		$email_message = $this->parse_macros( $this->get_settings( 'email_message' ) );
		
		$email_from = $this->parse_macros( $this->get_settings( 'email_from' ) );
		$email_from_name = $this->parse_macros( $this->get_settings( 'email_from_name' ) );

		$this->update_settings( 'email_from', $email_from );
		$this->update_settings( 'email_from_name', $email_from_name );

		$this->send_mail( $email_to, $email_subject, $email_message );

	}

	/**
	 * Send the email
	 *
	 * The To address to send to.
	 *
	 * @param $to
	 *
	 * The subject line of the email to send.
	 * @param $subject
	 *
	 * The body of the email to send.
	 * @param $message
	 *
	 * @return void
	 * @throws Action_Exception
	 */
	public function send_mail( $to, $subject, $message ) {

		/**
		 * Hooks before the email is sent
		 */
		$this->send_before();
		do_action( 'jet-apb/workflows/send-email/send-before', $this );

		$content_type = $this->get_content_type();

		if ( 'text/html' === $content_type ) {
			$message = make_clickable( wpautop( $message ) );
		}

		$message = str_replace( '&#038;', '&amp;', $message );
		$message = stripcslashes( $message );

		if ( Plugin::instance()->workflows->is_debug() ) {
			echo 'Email to: ' . $to . '<br>';
			echo 'Email Subject: ' . $subject . '<br>';
			echo 'Email Body: ' . $message . '<br>';
		} else {
			$sent = wp_mail(
				$to,
				$subject,
				$message,
				$this->get_headers()
			);

			if ( ! $sent ) {
				error_log( $message );
			}
		}
		

		/**
		 * Hooks after the email is sent
		 */
		$this->send_after();
		do_action( 'jet-apb/workflows/send-email/send-after', $this );
	}


	/**
	 * Get the email headers
	 */
	public function get_headers() {
		
		$headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
		$headers .= "Reply-To: {$this->get_from_address()}\r\n";
		$headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";

		return apply_filters( 'jet-apb/workflows/send-email/headers', $headers, $this );
	}

	/**
	 * Add filters / actions before the email is sent
	 */
	public function send_before() {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Remove filters / actions after the email is sent
	 */
	public function send_after() {
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		// Reset heading to an empty string
		$this->heading = '';
	}

	/**
	 * Get the email from name
	 */
	public function get_from_name() {

		$name = $this->get_settings( 'email_from_name' );
		$name = ! empty( $name ) ? $name : get_bloginfo( 'name' );

		return apply_filters( 'jet-apb/workflows/send-email/from-name', wp_specialchars_decode( $name ), $this );
	}

	/**
	 * Get the email from address
	 */
	public function get_from_address() {

		$address = $this->get_settings( 'email_from' );

		if ( empty( $address ) || ! is_email( $address ) ) {
			$address = get_option( 'admin_email' );
		}

		return apply_filters( 'jet-apb/workflows/send-email/from-address', $address, $this );
	}

	/**
	 * Get the email content type
	 */
	public function get_content_type() {
		return apply_filters( 'jet-apb/workflows/send-email/content-type', 'text/html', $this );
	}

}
