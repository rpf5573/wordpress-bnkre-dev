<?php

if ( ! defined('ABSPATH') ) {
	exit;
}

// wp_enqueue_script( 'mylisting-explore' );

/**
 * Explore page options.
 */
$data = c27()->merge_options([
	'title'    		 => '',
	'subtitle'       => '',
	'template' 		 => 'explore-default', // explore-default or explore-no-map
    'categories'     => [ 'count' => 10, ],
    'is_edit_mode'   => false,
    'scroll_to_results' => false,
    'disable_live_url_update' => false,
	'listing-wrap'   => '',
    'listing_types'  => [],
    'types_template' => 'topbar',
	'finder_columns' => 'finder-one-columns',
	'categories_overlay' => [
		'type' => 'gradient',
		'gradient' => 'gradient1',
		'solid_color' => 'rgba(0, 0, 0, .1)',
	],
	'map' => [
		'default_lat' => 51.492,
		'default_lng' => -0.130,
		'default_zoom' => 11,
		'min_zoom' => 2,
		'max_zoom' => 18,
		'skin' => 'skin1',
    	'scrollwheel' => false,
	],
], $data);

$GLOBALS['c27-explore'] = new MyListing\Src\Explore( $data );
$explore = &$GLOBALS['c27-explore'];

// 이제 여기서 data를 갖고와서 뿌려줘야함
$map = ML_Map::getInstance();
$requestArgs = $map->get_request_args($explore);

$listings = $map->run( $requestArgs );
?>
<script>
var listing_items = <?php echo json_encode( $listings ); ?>;
</script>
<?php

/*
 * Global variables.
 */
$GLOBALS['c27-facets-vue-object'] = [];

if ( ! in_array( $data['types_template'], ['topbar', 'dropdown'] ) ) {
	$data['types_template'] = 'topbar';
}

/*
 * The maximum number of columns for explore-2 template is "two". So, if the user sets
 * the option to "three" in Elementor settings, convert it to "two" columns.
 */
if ( $data['template'] == 'explore-2' && $data['finder_columns'] == 'finder-three-columns' ) {
	$data['finder_columns'] = 'finder-two-columns';
}
?>

<?php if (!$data['template'] || $data['template'] == 'explore-1' || $data['template'] == 'explore-2'): ?>
	<?php require locate_template( 'templates/explore/regular.php' ) ?>
<?php endif ?>

<?php if ($data['template'] == 'explore-no-map'): ?>
	<?php require locate_template( 'templates/explore/alternate.php' ) ?>
<?php endif ?>

<script type="text/javascript">
	var CASE27_Explore_Settings = {
		ListingWrap: <?php echo json_encode( $data['listing-wrap'] ) ?>,
		ActiveMobileTab: <?php echo json_encode( $explore->get_active_mobile_tab() ) ?>,
		ScrollToResults: <?php echo json_encode( $data['scroll_to_results'] ) ?>,
		Map: <?php echo wp_json_encode( $data['map'] ) ?>,
		IsFirstLoad: true,
		DisableLiveUrlUpdate: <?php echo json_encode( $data['disable_live_url_update'] ) ?>,
		FieldAliases: <?php echo json_encode( array_flip( array_merge( \MyListing\Src\Listing::$aliases, [
			'date_from' => 'job_date_from',
			'date_to' => 'job_date_to',
			'lat' => 'search_location_lat',
			'lng' => 'search_location_lng',
		] ) ) ) ?>,
		TermSettings: <?php echo wp_json_encode( $data['categories'] ) ?>,
		ListingTypes: <?php echo wp_json_encode( $explore->get_types_config( $GLOBALS['c27-facets-vue-object'] ) ) ?>,
		ExplorePage: <?php echo wp_json_encode( $explore::$explore_page && is_page( $explore::$explore_page->ID ) ? get_permalink( $explore::$explore_page ) : null ) ?>,
		ActiveListingType: <?php echo wp_json_encode( $explore->active_listing_type ? $explore->active_listing_type->get_slug() : null ) ?>,
		TermCache: {},
	};
</script>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">case27_ready_script(jQuery); MyListing.Explore_Init(); MyListing.Maps.init();</script>
<?php endif ?>
