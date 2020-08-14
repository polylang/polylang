<?php

use Psr\Log\LoggerInterface;

/**
 * Class PLL_Settings_Logger
 *
 * @since 2.9
 *
 * A PSR-3 compatible logger that uses WordPress's Settings API. {@link https://developer.wordpress.org/plugins/settings/settings-api/}
 */
class PLL_Settings_Logger implements LoggerInterface
{
	private $setting;
	private $interpolated_messages = array();

	public function __construct( $setting ) {
		$this->setting = $setting;
	}

	public function emergency($message, array $context = array())
	{
		$this->error( $message, $context );
	}

	public function alert($message, array $context = array())
	{
		$this->error( $message, $context );

	}

	public function critical($message, array $context = array())
	{
		$this->error( $message, $context );
	}

	public function error($message, array $context = array())
	{
		$this->log( 'error', $message, $context );
	}

	public function warning($message, array $context = array())
	{
		$this->log( 'warning', $message, $context );
	}

	public function notice( $message, array $context = array() )
	{
		$this->log( 'success', $message, $context );
	}

	public function info($message, array $context = array())
	{
		$this->log( 'info', $message, $context );
	}

	public function debug($message, array $context = array())
	{
		$this->log ( 'info', $message, $context );
	}

	public function log($level, $message, array $context = array())
	{
		global $wp_settings_errors;

		$code = isset($context['code']) ? $context['code'] : $this->setting . '_' . $level;

		$message = $this->interpolate($message, $context);

		$setting_error = array(
			'setting' => $this->setting,
			'code' => $code,
			'message' => esc_html( $message ),
			'type' => $level
		);

		// Because WordPress Setting API doesn't allow to update registered errors, we need to do it on our own.
		// Beware that $context['code'] !== $code, because if the code is not set from the context, we will fallback to a default one. But we will allow to register several errors with the default code.
		if ( isset( $context['code'] ) && array_filter(
			$wp_settings_errors,
			function ( $entry ) use ( $setting_error ) {
				return $entry['code'] === $setting_error['code'];
			} )
		) {
			$wp_settings_errors = array_map(
				function( $entry ) use ( $setting_error ) {
					if ($entry['code'] === $setting_error['code']) {
						return $setting_error;
					} else {
						return $entry;
					}
				},
				$wp_settings_errors
			);
		} else {
			add_settings_error(	$this->setting,	$code, esc_html( $message ), $level	);
		}
	}

	private function interpolate( $message, $context = array() ) {
		$placeholder_values = array_filter( $context, array( $this, 'is_placeholder' ), ARRAY_FILTER_USE_BOTH );

		if ( ! empty( $placeholder_values ) ) {
			if ( $this->is_already_interpolated($context)  ) {
				$placeholder_values = $this->increment_values($placeholder_values, $context['code']);
				$message = $this->get_message_template($context['code']);
			}

			$message = new PLL_Settings_Message_Incremental( $message, $placeholder_values );

			if ( isset( $context['code'] ) ) {
				$this->interpolated_messages[ $context['code'] ] = array(
					'message_template' => $message,
					'placeholder_values' => $placeholder_values
				);
			}
		}

		$replace = array();
		foreach ( $placeholder_values as $key => $value ) {
			$replace['{' . $key . '}'] = $value;
		}

		return strtr( strval( $message ), $replace);
	}

	/**
	 * @param $value
	 * @param $key
	 * @return bool
	 */
	private function is_placeholder($value, $key)
	{
		return 'code' !== $key &&
			!is_array($value) &&
			(!is_object($value) || method_exists($value, '__toString'));
	}

	/**
	 * @param array $context
	 * @return bool
	 */
	private function is_already_interpolated(array $context)
	{
		return isset($context['code']) && array_key_exists($context['code'], $this->interpolated_messages);
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	private function get_message_template($code)
	{
		return $this->interpolated_messages[$code]['message_template'];
	}

	/**
	 * @param array $placeholder_values
	 * @param $code
	 * @return array
	 */
	private function increment_values(array $placeholder_values, $code)
	{
		foreach ($placeholder_values as $key => $value) {
			if (is_array($value)) {
				$placeholder_values[$key] = array_merge($value, $this->interpolated_messages[$code]['placeholder_value']);
			} elseif (is_numeric($value)) {
				$placeholder_values[$key] = $value + $this->interpolated_messages[$code]['placeholder_values'][$key];
			}
		}
		return $placeholder_values;
	}

}
