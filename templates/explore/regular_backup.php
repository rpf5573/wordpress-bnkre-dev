<?php
/**
 * Template for displaying regular Explore page with map.
 *
 * @var   $data
 * @var   $explore
 * @since 2.0
 */

$data['listing-wrap'] = 'col-md-12 grid-item';
?>
<div class="cts-explore finder-container fc-type-1 <?php echo esc_attr( $data['finder_columns'] ) ?> <?php echo $data['finder_columns'] == 'finder-three-columns' ? 'fc-type-1-no-map' : '' ?> <?php echo $data['types_template'] === 'dropdown' ? 'explore-types-dropdown' : 'explore-types-topbar' ?>" id="c27-explore-listings">
	<div class="mobile-explore-head">
		<a type="button" class="toggle-mobile-search" data-toggle="collapse" data-target="#finderSearch"><i class="material-icons sm-icon">sort</i><?php _e( 'Search Filters', 'my-listing' ) ?></a>
	</div>

	<?php if ( $data['types_template'] === 'topbar' ): ?>
		<?php require locate_template( 'templates/explore/partials/topbar.php' ) ?>
	<?php endif ?>
	
	<div class="<?php echo $data['template'] == 'explore-2' ? 'fc-one-column' : 'fc-default' ?>">
		<div class="finder-search" id="finderSearch" :class="( state.mobileTab === 'filters' ? '' : 'visible-lg' )">
			<div class="finder-tabs-wrapper">
				<?php require locate_template( 'templates/explore/partials/sidebar.php' ) ?>
			</div>
		</div>

		<div class="finder-listings" id="finderListings" :class="( state.mobileTab === 'results' ? '' : 'visible-lg' )">
			<div class="fl-head">
				<div class="col-xs-4 sort-results showing-filter" v-cloak>
					<?php foreach ( $explore->types as $type ): ?>
						<?php require locate_template('partials/facets/order.php') ?>
					<?php endforeach ?>
				</div>

				<div class="col-xs-4 text-center">
					<span href="#" class="fl-results-no text-left" v-cloak>
						<span></span>
					</span>
				</div>

				<?php if ( $data['finder_columns'] != 'finder-three-columns' ): ?>
					<div class="col-xs-4 map-toggle-button">
						<a href="#" class=""><?php _e( 'Map view', 'my-listing' ) ?><i class="material-icons sm-icon">map</i></a>
					</div>

					<div class="col-xs-4 column-switch">
						<a href="#" class="col-switch switch-one <?php echo $data['finder_columns'] == 'finder-one-columns' ? 'active' : '' ?>" data-no="finder-one-columns">
							<i class="material-icons">view_stream</i>
						</a>
						<a href="#" class="col-switch switch-two <?php echo $data['finder_columns'] == 'finder-two-columns' ? 'active' : '' ?>" data-no="finder-two-columns">
							<i class="material-icons">view_module</i>
						</a>
						<a href="#" class="col-switch switch-three <?php echo $data['finder_columns'] == 'finder-three-columns' ? 'active' : '' ?>" data-no="finder-three-columns">
							<i class="material-icons">view_comfy</i>
						</a>
					</div>
				<?php endif ?>
			</div>
			<div class="results-view grid" v-show="!loading"> <?php

				$args = [
					'order' => 'DESC',
					'offset' => 0,
					'orderby' => 'date',
					'posts_per_page' => 1000,
					'tax_query' => [],
					'meta_query' => [],
				];
				$args['meta_query']['listing_type_query'] = [
					'key'     => '_case27_listing_type',
					'value'   =>  'building',
					'compare' => '='
				];
				$explore_tab = get_query_var( 'explore_tab' );
				$taxonomy = '';
				if ( $explore_tab == 'tags' ) {
					$taxonomy = 'case27_job_listing_tags';
				}
				else if ( $explore_tab == 'regions' ) {
					$taxonomy = 'region';
				}

				$tag_slug = get_query_var('explore_tag');
				$region_slug = get_query_var( 'explore_region' );
				$slug = '';
				if ( $tag_slug ) {
					$slug = $tag_slug;
				} 
				else if ( $region_slug ) {
					$slug = $region_slug;
				}
				// explore_tag
				$args['tax_query'][] = [
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $slug,
					'operator' => "IN",
					'include_children' => true,
				];
				$listing_thumbnail = c27()->image( 'marker.jpg' );
				$listings = new WP_Query($args);
				while ( $listings->have_posts() ) : $listings->the_post();
					global $post;
					$latitude = false;
					$longitude = false;
					$listing = \MyListing\Src\Listing::get( $post );
					if ( is_numeric( $listing->get_data('geolocation_lat') ) ) {
						$latitude = $listing->get_data('geolocation_lat');
					}
					if ( is_numeric( $listing->get_data('geolocation_long') ) ) {
						$longitude = $listing->get_data('geolocation_long');
					} ?>
					<div
						class="<?php echo 'lf-item-container'; ?>"
						data-id="listing-id-<?php echo esc_attr( $listing->get_id() ); ?>"
						data-latitude="<?php echo esc_attr( $latitude ); ?>"
						data-longitude="<?php echo esc_attr( $longitude ); ?>"
						data-thumbnail="<?php echo esc_url( $listing_thumbnail ) ?>"
					></div> <?php
				endwhile;
				wp_reset_postdata();
?>
			</div>
			<div class="loader-bg" v-show="loading">
				<?php c27()->get_partial( 'spinner', [
					'color' => '#777',
					'classes' => 'center-vh',
					'size' => 28,
					'width' => 3,
				] ) ?>
			</div>
			<div class="col-md-12 center-button pagination c27-explore-pagination" v-show="!loading"></div>
		</div>
	</div>

	<?php if ( $data['finder_columns'] != 'finder-three-columns' ): ?>
		<div class="finder-map" id="finderMap" :class="( state.mobileTab === 'map' ? 'map-mobile-visible' : '' )">
			<div class="map c27-map mylisting-map-loading" id="<?php echo esc_attr( 'map__' . uniqid() ) ?>" data-options="<?php echo htmlspecialchars(json_encode([
				'skin' => $data['map']['skin'],
				'scrollwheel' => $data['map']['scrollwheel'],
				'zoom' => 10,
				'minZoom' => $data['map']['min_zoom'],
				'maxZoom' => $data['map']['max_zoom'],
			]), ENT_QUOTES, 'UTF-8'); ?>">
			</div>
		</div>
		<div style="display: none;">
			<div id="explore-map-location-ctrl" title="<?php echo esc_attr( _x( 'Click to show your location', 'Explore page', 'my-listing' ) ) ?>">
				<i class="mi my_location"></i>
			</div>
		</div>
	<?php endif ?>

	<?php require locate_template( 'templates/explore/partials/mobile-nav.php' ) ?>
</div>