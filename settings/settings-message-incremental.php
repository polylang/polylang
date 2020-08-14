<?php


class PLL_Settings_Message_Incremental
{

    /**
     * PLL_Settings_Message_Incremental constructor.
     * @param Closure $param
     */
    public function __construct($message, $placeholder_values = array() ) {
		$this->message = $message;
		$this->placeholder_values = $placeholder_values;
    }

    public function __invoke( $args )
	{
		return call_user_func( $this->message, $args );
	}

	public function __toString() {
		return call_user_func( $this->message, $this->placeholder_values );
	}
}
