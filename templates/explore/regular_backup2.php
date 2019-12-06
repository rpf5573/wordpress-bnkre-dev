<?php
/**
 * Template for displaying regular Explore page with map.
 *
 * @var   $data
 * @var   $explore
 * @since 2.0
 */


$data['listing-wrap'] = 'col-md-12 grid-item';
$map = ML_Map::getInstance();
?>
<div class="cts-explore finder-container fc-type-1 <?php echo esc_attr( $data['finder_columns'] ) ?> <?php echo $data['finder_columns'] == 'finder-three-columns' ? 'fc-type-1-no-map' : '' ?> <?php echo $data['types_template'] === 'dropdown' ? 'explore-types-dropdown' : 'explore-types-topbar' ?>" id="c27-explore-listings">
	<div class="explore-title"> <?php
		echo urldecode($map->title); ?>
	</div><?php	
	if ( $map->has_listings ) { ?>
		<div class="explore-pagination-container center-button"> <?php
			echo $map->pagination(); ?>
		</div>
		<div id="finderMap" class="finder-map map-mobile-visible">
			<div class="map c27-map mylisting-map-loading" id="<?php echo esc_attr( 'map__' . uniqid() ) ?>" data-options="<?php echo htmlspecialchars(json_encode([
				'skin' => $data['map']['skin'],
				'scrollwheel' => $data['map']['scrollwheel'],
				'zoom' => 10,
				'minZoom' => $data['map']['min_zoom'],
				'maxZoom' => $data['map']['max_zoom'],
			]), ENT_QUOTES, 'UTF-8'); ?>">
			</div>
		</div> <?php
		// require locate_template( 'templates/explore/partials/mobile-nav.php' );
	} else {
		require locate_template( 'partials/no-listings-found.php' );
	} ?>
</div>