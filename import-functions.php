<?php
// [set_listing_type_of_term({categories[1]}, ">", "job_listing_category", 343)]


// 343 => building
// 344 => restaurant
// 345 => warehouse
// 346 => factory

// taxonomy => ['job_listing_category', 'region', 'case27_job_listing_tags']
function set_listing_type_of_term( $string_terms, $seperator, $taxonomy, $listing_type ) {
  $original = $string_terms;
  $terms = explode( $seperator, $string_terms );
  foreach( $terms as $key => $val ) {
    $term = get_term_by('name', $val, $taxonomy);
    if ( $term ) {
      $term_id = $term->term_id;
      $result = update_term_meta( $term_id, 'listing_type', [$listing_type] );
    }
  }
	return $original;
}?>