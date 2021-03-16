<?php 

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('ACF_Taxonomy_Field_Walker') ) :

class ACF_Taxonomy_Field_Walker extends Walker {
	
	var $field = null,
		$tree_type = 'category',
		$db_fields = array ( 'parent' => 'parent', 'id' => 'term_id' );
	
	function __construct( $field ) {
	
		$this->field = $field;
		
	}

	function start_el( &$output, $term, $depth = 0, $args = array(), $current_object_id = 0) {
		
                global $wpdb;
		// vars
		$selected = in_array( $term->term_id, $this->field['value'] );
                
		$term_id = $term->term_id;
                
                $sql_query_term = "SELECT pm.meta_value as pmmv, pm.meta_key as pmmk, t.name 
                    FROM " . $wpdb->prefix . "terms as t
                    LEFT JOIN " . $wpdb->prefix . "termmeta as tm ON tm.term_id = t.term_id 
                    LEFT JOIN " . $wpdb->prefix . "postmeta as pm ON pm.post_id = tm.meta_value
                    WHERE tm.term_id = '" .  strtolower($term_id) . "'
                    AND tm.meta_key='image' AND pm.meta_key='_wp_attached_file'";

                $result_query = $wpdb->get_results($sql_query_term);
                $image_source = "";
                if(isset($result_query[0]) && $result_query[0] != "") {
                    $img = $result_query[0]->pmmv;
                    $image_link = get_bloginfo('url') . "/wp-content/uploads/" . $img;
                    $image_source = '<label style="height:50px;width: 65px;float: left;"><img style="padding-left: 10px;top: -5px;position: absolute;" src="' . $image_link . '" width="40" height="40" alt=""></label>';
                }
                
		// append
		$output .= '<li style="height:50px;width: 33%;float: left;" data-id="' . $term->term_id . '">' . $image_source . '<label' . ($selected ? ' class="selected"' : '') . '><input type="' . $this->field['field_type'] . '" name="' . $this->field['name'] . '" value="' . $term->term_id . '" ' . ($selected ? 'checked="checked"' : '') . ' /> <span>' . $term->name . '</span></label>';
				
	}
	
	function end_el( &$output, $term, $depth = 0, $args = array() ) {
	
		// append
		$output .= '</li>' .  "\n";
		
	}
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		
		// append
		$output .= '<ul class="children acf-bl">' . "\n";
		
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
	
		// append
		$output .= '</ul>' . "\n";
		
	}
	
}

endif;

 ?>