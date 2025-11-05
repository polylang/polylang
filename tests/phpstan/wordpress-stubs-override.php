<?php

/**
 * Accepts anything and returns a string.
 *
 * @param mixed $key
 * @return string
 */
function sanitize_key( $key ) {}

/**
 * Accepts only objects, arrays and strings and always returns a scalar.
 *
 * @param string|array<mixed>|object $data
 * @return scalar
 */
function maybe_serialize( $data ) {}

/**
 * Accepts anything and returns a string. @see {_sanitize_text_field()}.
 *
 * @param mixed $str
 * @return string
 */
function sanitize_text_field( $str ) {}
