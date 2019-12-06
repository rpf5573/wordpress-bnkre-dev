<?php
class ML_Map extends MyListing\Src\Queries\Explore_Listings {
	public static $instance;
	public $max_num_page;
  public $paged;
  public $has_listings;
	public $title;
	public $BUILDING_TYPE = 13;
	public $RESTAURANT_TYPE = 'restaurant';
	public $WAREHOUSE_TYPE = 'warehouse';
	public $BUILDING_TYPE_PIN_LOGO_URL = 'https://bnkre.com/wp-content/uploads/2019/11/marker.jpg';
	public $RESTAURANT_TYPE_PIN_LOGO_URL = 'https://bnkre.com/wp-content/uploads/2019/11/michelin_pin.png';
	public $WAREHOUSE_TYPE_PIN_LOGO_URL = 'https://bnkre.com/wp-content/uploads/2019/11/marker.jpg';

	public static function getInstance() {
    // Check is $_instance has been set
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    // Returns the instance
    return self::$instance;
  }
}