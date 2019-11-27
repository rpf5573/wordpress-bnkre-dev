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
	public $BUILDING_TYPE_PIN_LOGO_URL = 'http://localhost:8888/bnkre/wp-content/uploads/2019/11/marker.jpg';
	public $RESTAURANT_TYPE_PIN_LOGO_URL = 'http://localhost:8888/bnkre/wp-content/uploads/2019/11/michelin_pin.png';
	public $WAREHOUSE_TYPE_PIN_LOGO_URL = 'http://localhost:8888/bnkre/wp-content/uploads/2019/11/marker.jpg';
	
	public function get_request_args(&$explore) {
		$this->paged = get_query_var( 'paged', 0 );
		$s = isset($_GET['search_keywords']) ? $_GET['search_keywords'] : null;
		$taxonomy = null;
		$term = null;
		foreach( $explore->taxonomies as $taxObj ) {
			$activeTermId = $taxObj['activeTermId'];
			if ( $activeTermId > 0 ) {
				$taxonomy = $taxObj['tax'];
				$term = $activeTermId;
				$termObject = get_term_by( 'id', $activeTermId, $taxonomy);
				$this->title = $termObject->name;
			}
		}
		if ( ! is_null($s) ) {
			$this->title = $s;
		}
		$args = array(
			'listing_type' => ( $explore->active_listing_type ? $explore->active_listing_type->get_slug() : null ),
			'form_data' => [
				'page' => $this->paged,
				'taxonomy' => $taxonomy,
				'term'	=> $term,
				'search_keywords' => $s,
				'context' => is_null($term) ? null : 'term-search'
			],
			'return_listings' => true,
			'global_search' => is_null($s) ? false : true // 검색시에 모든 type을 포함시킬지 안시킬지를 결정합니다.
		);
		\PC::debug( ['args' => $args], __FUNCTION__ );
		return $args;
	}
	public static function getInstance() {
    // Check is $_instance has been set
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    // Returns the instance
    return self::$instance;
  }
	/**
	 * Handle Explore Listings requests, typically $_POST.
	 * Request can be manually constructed, which allows using
	 * this function outside Ajax/POST context.
	 *
	 * @since 1.7.0
	 */
	public function run( $request ) {
		global $wpdb;

		if ( empty( $request['form_data'] ) || ! is_array( $request['form_data'] ) || empty( $request['listing_type'] ) ) {
			return false;
		}

		if ( ! ( $listing_type_obj = ( get_page_by_path( $request['listing_type'], OBJECT, 'case27_listing_type' ) ) ) ) {
			return false;
		}
 
		$type = new \MyListing\Ext\Listing_Types\Listing_Type( $listing_type_obj );
		$form_data = $request['form_data'];

		$page = absint( isset($form_data['page']) ? $form_data['page'] : 0 );
		$per_page = absint( isset($form_data['per_page']) ? $form_data['per_page'] : c27()->get_setting('general_explore_listings_per_page', 9));
		$orderby = sanitize_text_field( isset($form_data['orderby']) ? $form_data['orderby'] : 'date' );
		$context = sanitize_text_field( isset( $form_data['context'] ) ? $form_data['context'] : 'advanced-search' );
		$args = [
			'order' => sanitize_text_field( isset($form_data['order']) ? $form_data['order'] : 'DESC' ),
			'offset' => $per_page * ( $page < 2 ? 0 : $page - 1 ),
			'orderby' => $orderby,
			'posts_per_page' => $per_page,
			'tax_query' => [],
			'meta_query' => [],
		];

		$this->get_ordering_clauses( $args, $type, $form_data );

		// Make sure we're only querying listings of the requested listing type.
		if ( ! $type->is_global() && ! $request['global_search'] ) {
			$args['meta_query']['listing_type_query'] = [
				'key'     => '_case27_listing_type',
				'value'   =>  $type->get_slug(),
				'compare' => '='
			];
		}

		if ( $context === 'term-search' ) {
			$taxonomy = ! empty( $form_data['taxonomy'] ) ? sanitize_text_field( $form_data['taxonomy'] ) : false;
			$term = ! empty( $form_data['term'] ) ? sanitize_text_field( $form_data['term'] ) : false;

			if ( ! $taxonomy || ! $term || ! taxonomy_exists( $taxonomy ) ) {
				return false;
			}

			$tax_query_operator = apply_filters( 'mylisting/explore/match-all-terms', false ) === true ? 'AND' : 'IN';
			$args['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'field' => 'term_id',
				'terms' => $term,
				'operator' => $tax_query_operator,
				'include_children' => $tax_query_operator !== 'AND',
			];

			// add support for nearby order in single term page
			if ( isset( $form_data['proximity'], $form_data['search_location_lat'], $form_data['search_location_lng'] ) ) {
				$proximity = absint( $form_data['proximity'] );
				$location = isset( $form_data['search_location'] ) ? sanitize_text_field( stripslashes( $form_data['search_location'] ) ) : false;
				$lat = (float) $form_data['search_location_lat'];
				$lng = (float) $form_data['search_location_lng'];
				$units = isset($form_data['proximity_units']) && $form_data['proximity_units'] == 'mi' ? 'mi' : 'km';
				if ( $lat && $lng && $proximity && $location ) {
					$earth_radius = $units == 'mi' ? 3959 : 6371;
					$sql = $wpdb->prepare( $this->get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );
					$post_ids = (array) $wpdb->get_results( $sql, OBJECT_K );
					if ( empty( $post_ids ) ) { $post_ids = ['none']; }
					$args['post__in'] = array_keys( (array) $post_ids );
					$args['search_location'] = '';
				}
			}
		} else {
			foreach ( (array) $type->get_search_filters() as $facet ) {
				// wp-search -> search_keywords
				// location -> search_location
				// text -> facet.show_field
				// proximity -> proximity
				// date -> show_field
				// range -> show_field
				// dropdown -> show_field
				// checkboxes -> show_field

				if ( $facet['type'] === 'wp-search' && ! empty( $form_data['search_keywords'] ) ) {
					
					// dd($form_data['search_keywords']);
					$args['search_keywords'] = sanitize_text_field( stripslashes( $form_data['search_keywords'] ) );
				}

				if ( $facet['type'] === 'location' && ! empty( $form_data['search_location'] ) ) {
					$args['search_location'] = sanitize_text_field( stripslashes( $form_data['search_location'] ) );
				}

				if ($facet['type'] == 'text' && isset($form_data[$facet['show_field']]) && $form_data[$facet['show_field']]) {
					$args['meta_query'][] = [
						'key'     => "_{$facet['show_field']}",
						'value'   => sanitize_text_field( stripslashes( $form_data[$facet['show_field']] ) ),
						'compare' => 'LIKE',
					];
				}

				if ($facet['type'] == 'proximity' && isset($form_data['proximity']) && isset($form_data['search_location_lat']) && isset($form_data['search_location_lng'])) {
					$proximity = absint( $form_data['proximity'] );
					$location = isset($form_data['search_location']) ? sanitize_text_field( stripslashes( $form_data['search_location'] ) ) : false;
					$lat = (float) $form_data['search_location_lat'];
					$lng = (float) $form_data['search_location_lng'];
					$units = isset($form_data['proximity_units']) && $form_data['proximity_units'] == 'mi' ? 'mi' : 'km';

					if ( $lat && $lng && $proximity && $location ) {
						// dump($lat, $lng, $proximity);

						$earth_radius = $units == 'mi' ? 3959 : 6371;

						$sql = $wpdb->prepare( $this->get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );

						// dump($sql);

						$post_ids = (array) $wpdb->get_results( $sql, OBJECT_K );

						if (empty($post_ids)) $post_ids = ['none'];

						$args['post__in'] = array_keys( (array) $post_ids );

						// Remove search_location filter when using proximity filter.
						$args['search_location'] = '';
					}
				}

				if ($facet['type'] == 'date') {
					$date_type = 'exact';
					$format = 'ymd';

					foreach ($facet['options'] as $option) {
						if ($option['name'] == 'type') $date_type = $option['value'];
						if ($option['name'] == 'format') $format = $option['value'];
					}

					// Exact date search.
					if ($date_type == 'exact' && isset($form_data[$facet['show_field']]) && $form_data[$facet['show_field']]) {
						// Y-m-d format search.
						if ($format == 'ymd') {
							$date = date('Y-m-d', strtotime( $form_data[$facet['show_field']] ));
							$compare = '=';
						}

						// Year search. The year is converted to a date format, and the query instead runs a 'BETWEEN' comparison,
						// to include the requested year from January 01 to December 31.
						if ($format == 'year') {
							$date = [
								date('Y-01-01', strtotime($form_data[$facet['show_field']] . '-01-01' )),
								date('Y-12-31', strtotime($form_data[$facet['show_field']] . '-12-31')),
							];
							$compare = 'BETWEEN';
						}

						$args['meta_query'][] = [
							'key'     => "_{$facet['show_field']}",
							'value'   => $date,
							'compare' => $compare,
							'type' => 'DATE',
						];
					}

					// Range date search.
					if ($date_type == 'range') {
						$date_from = false;
						$date_to = false;
						$values = [];

						if (isset($form_data["{$facet['show_field']}_from"]) && $form_data["{$facet['show_field']}_from"]) {
							$date_from = $values['date_from'] = date(($format == 'ymd' ? 'Y-m-d' : 'Y'), strtotime( $form_data["{$facet['show_field']}_from"] ));

							if ($format == 'ymd') {
								$date_from = $values['date_from'] = date('Y-m-d', strtotime($form_data["{$facet['show_field']}_from"]));
							}

							if ($format == 'year') {
								$date_from = $values['date_from'] = date('Y-m-d', strtotime($form_data["{$facet['show_field']}_from"] . '-01-01'));
							}
						}

						if (isset($form_data["{$facet['show_field']}_to"]) && $form_data["{$facet['show_field']}_to"]) {
							if ($format == 'ymd') {
								$date_to = $values['date_to'] = date('Y-m-d', strtotime($form_data["{$facet['show_field']}_to"]));
							}

							if ($format == 'year') {
								$date_to = $values['date_to'] = date('Y-m-d', strtotime($form_data["{$facet['show_field']}_to"] . '-12-31'));
							}
						}

						if (empty($values)) continue;
						if (count($values) == 1) $values = array_pop($values);

						$args['meta_query'][] = [
							'key'     => "_{$facet['show_field']}",
							'value'   => $values,
							'compare' => is_array($values) ? 'BETWEEN' : ($date_from ? '>=' : '<='),
							'type' => 'DATE',
						];
					}
				}

				if ($facet['type'] == 'range' && isset($form_data[$facet['show_field']]) && $form_data[$facet['show_field']] && isset($form_data["{$facet['show_field']}_default"])) {
					$range_type = 'range';
					$range = $form_data[$facet['show_field']];
					$default_range = $form_data["{$facet['show_field']}_default"];

					// In case the range values include the maximum and minimum possible field values,
					// then skip, since the meta query is unnecessary, and would only make the query slower.
					if ($default_range == $range) continue;

					foreach ($facet['options'] as $option) {
						if ($option['name'] == 'type') $range_type = $option['value'];
					}

					if ($range_type == 'range' && strpos($range, '::') !== false) {
						$args['meta_query'][] = [
							'key'     => "_{$facet['show_field']}",
							'value'   => array_map('intval', explode('::', $range)),
							'compare' => 'BETWEEN',
							'type'    => 'NUMERIC',
						];
					}

					if ($range_type == 'simple') {
						$args['meta_query'][] = [
							'key'     => "_{$facet['show_field']}",
							'value'   => intval( $range ),
							'compare' => '<=',
							'type'    => 'NUMERIC',
						];
					}
				}

				if (($facet['type'] == 'dropdown' || $facet['type'] == 'checkboxes') && ! empty( $form_data[$facet['show_field']] ) ) {
					$dropdown_values = array_filter( array_map('stripslashes', (array) $form_data[$facet['show_field']] ) );

					if (!$dropdown_values) continue;

					if ( empty( $facet['options'] ) ) {
						$facet['options'] = [];
					}

					$facet_behavior = 'any';
					foreach ( (array) $facet['options'] as $facet_option ) {
						if ( $facet_option['name'] === 'behavior' ) {
							$facet_behavior = $facet_option['value'];
						}
					}

					// Tax query.
					if (
						$type->get_field( $facet[ 'show_field' ] ) &&
						! empty( $type->get_field( $facet[ 'show_field' ] )['taxonomy'] ) &&
						taxonomy_exists( $type->get_field( $facet[ 'show_field' ] )['taxonomy'] )
					) {
						$args['tax_query'][] = [
							'taxonomy' => $type->get_field( $facet[ 'show_field' ] )['taxonomy'],
							'field' => 'slug',
							'terms' => $dropdown_values,
							'operator' => $facet_behavior === 'all' ? 'AND' : 'IN',
							'include_children' => $facet_behavior !== 'all',
						];

						continue;
					}

					// If the meta value is serialized.
					if ( $type->get_field( $facet[ 'show_field' ] ) && $type->get_field( $facet[ 'show_field' ] )['type'] == 'multiselect' ) {
						$subquery = [
							'relation' => $facet_behavior === 'all' ? 'AND' : 'OR',
						];

						foreach ( $dropdown_values as $dropdown_value ) {
							$subquery[] = [
								'key'     => "_{$facet['show_field']}",
								'value'   => '"' . $dropdown_value . '"',
								'compare' => 'LIKE',
							];
						}

						$args['meta_query'][] = $subquery;
						continue;
					}

					$args['meta_query'][] = [
						'key'     => "_{$facet['show_field']}",
						'value'   => $dropdown_values,
						'compare' => 'IN',
					];
				}
			}
		}

		$results = [];
		$result['found_jobs'] = false;
		$listing_wrap = ! empty( $request['listing_wrap'] ) ? sanitize_text_field( $request['listing_wrap'] ) : '';

		/* Promotions v1 code (deprecated) */
		$result['promoted_ids']  = [];
		$result['promoted_html'] = '';

		/**
		 * Hook after the search args have been set, but before the query is executed.
		 *
		 * @since 1.7.0
		 */
		do_action_ref_array( 'mylisting/get-listings/before-query', [ $args, $type, $result ] );

		

		$listings = $this->query( $args );

		if ( ! empty( $request['return_query'] ) ) {
			return $listings;
		}

		if ( ! empty( $request['return_listings'] ) ) {
			$listing_query = $listings;
			
			$this->has_listings = ($listing_query->post_count > 0) ? true : false;
			$this->max_num_page = $listing_query->max_num_pages;
			$listings = [];
			while( $listing_query->have_posts() ) {
				$listing_query->the_post();
				global $post;
				$post_id = $post->ID;
				$latitude = get_post_meta( $post_id, 'geolocation_lat', true );
				$longitude = get_post_meta( $post_id, 'geolocation_long', true );
				$title = isset( $post->post_title ) ? $post->post_title : '';
				$link = $post->guid;
				$backimage = get_post_meta( $post_id, '_job_cover', true );
				$listing_type = get_post_meta( $post_id, '_case27_listing_type', true );
				$thumbnail = $this->get_pin_logo( $listing_type );
				$backimage = maybe_unserialize( $backimage );
				if ( is_array($backimage) && count($backimage) > 0 ) {
					$backimage = $backimage[0];
				}
				$listings[] = array(
					'id' => $post_id,
					'lat' => $latitude,
					'long' => $longitude,
					'title' => $title,
					'link' => $link,
					'bi' => $backimage,
					'thumbnail' => $thumbnail
				);
			}
			return $listings;
		}

		ob_start();

		if ( CASE27_ENV === 'dev' ) {
			$result['args'] = $args;
			$result['sql'] = $listings->request;
		}

		if ( $listings->have_posts() ) : $result['found_jobs'] = true;
			while ( $listings->have_posts() ) : $listings->the_post();
				/* Promotions v1 code (deprecated) */
				if ( absint( $listings->post_count ) > 3 && in_array( absint( get_the_ID() ), $result['promoted_ids'] ) ) {
					continue;
				}

				global $post;
				mylisting_locate_template( 'partials/listing-preview.php', [
					'listing' => $post,
					'wrap_in' => $listing_wrap,
				] );
			endwhile;

			$result['listings_html'] = ob_get_clean();

			if ( absint( $listings->post_count ) <= 3 ) {
				$result['html'] = $result['listings_html'];
			} else {
				$result['html'] = $result['promoted_html'] . $result['listings_html'];
			}

			wp_reset_postdata();
		else:
			require locate_template( 'partials/no-listings-found.php' );
			$result['html'] = ob_get_clean();
		endif;

		/* Promotions v1 code (deprecated) */
		unset( $result['promoted_ids'] );

		// Generate pagination
		$result['pagination'] = c27()->get_listing_pagination( $listings->max_num_pages, ($page + 1) );

		$result['showing'] = sprintf( __( '%d results', 'my-listing' ), $listings->found_posts);

		if ($listings->found_posts == 1) {
			$result['showing'] = __( 'One result', 'my-listing');
		}

		if ($listings->found_posts < 1) {
			$result['showing'] = __( 'No results', 'my-listing' );
		}

		$result['max_num_pages'] = $listings->max_num_pages;

		return $result;
	}
	public function get_pin_logo( $type ) {
		if ( $type == $this->BUILDING_TYPE ) {
			return $this->BUILDING_TYPE_PIN_LOGO_URL;
		}
		if ( $type == $this->RESTAURANT_TYPE ) {
			return $this->RESTAURANT_TYPE_PIN_LOGO_URL;
		}
		if ( $type == $this->WAREHOUSE_TYPE ) {
			return $this->WAREHOUSE_TYPE_PIN_LOGO_URL;
		}
	}
	public function pagination() {
    $prev_arrow = '<';
    $next_arrow = '>';
    $big = 99999;
    if ( $this->max_num_page > 1 ) {
      if ( !$this->paged ) {
        $this->paged = 1;
      }
      if ( get_option( 'permalink_structure' ) ) {
        $format = 'page/%#%/';
      } else {
        $format = '&paged=%#%';
      }
      $args = array(
        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link($big) ) ),
        'format'    => $format,
        'current'   => max( 1, $this->paged ),
        'mid_size'  => 3,
        'total'     => $this->max_num_page,
        'type'      => 'list',
        'prev_text' => $prev_arrow,
        'next_text' => $next_arrow
      );
      return paginate_links($args);
    } 
  }
}