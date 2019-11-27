<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class ML_Shortcodes {
  function __construct() {
    add_shortcode( 'ml_table', array($this, 'table') );
  }
  public function table() {
    $html = "<ul class='extra-details'>";
    foreach($atts as $key => $value) {
      $html .= "<li>
                  <div class='item-attr'>{$key}</div>
                  <div class='item-property'>{$value}</div>
                </li>";
    }
    $html .= '</ul>';
    return $html;
  }
}