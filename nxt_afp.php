<?php
/**
 * @package ajax_filter_custom_posts by nexTab
 * @version 1.0
 */
/*
Plugin Name: Ajax Posts Filter
Plugin URI: https://nextab.de
Description: This plugin allows you to filter a custom post type and display the results live with some Ajax magic.
Author: Oliver Gehrmann
Version: 1.0
Textdomain: nxt_afp
Author URI: https://nexTab.de/
*/

$nxt_post_type = 'electromobile';
$nxt_tax = 'elektromobil-hersteller';
$nxt_post_status = ['publish', 'draft'];

function nxt_afp_script_nq(){
	if (!is_admin()) {
		wp_register_script('nxt_afp_scripts', plugins_url('nxt_afp', dirname(__FILE__)).'/js/nxt_afp_scripts.js', ['jquery'], '', true);
		wp_enqueue_script('jquery');
		wp_enqueue_script('nxt_afp_scripts');
		wp_enqueue_style('nxt_afp_styles', plugins_url('nxt_afp', dirname(__FILE__)).'/css/nxt_afp_styles.css');
	}
}
if (function_exists('nxt_afp_script_nq')) add_action('wp_enqueue_scripts', 'nxt_afp_script_nq');

/* Filter Function for custom post type */
// This function creates 1) a search box 2) the default arguments for our custom query and it then calls another function to output the posts on our site
function nxt_afp_filter($atts = null, $content = null) {
	$return_string = '';
	global $nxt_post_type;
	global $nxt_tax;
	global $nxt_post_status;
	$a = shortcode_atts([
		'post_type' => $nxt_post_type,
		'post_status' => $nxt_post_status,
	], $atts);
	$return_string .= '<div id="nxt_afp_filter_container" class="nxt_afp_filter_container"><form action="' . site_url() . '/wp-admin/admin-ajax.php" method="POST" id="filter" class="nxt_afp_filter_form">';
	
	// Adding the search fields

	// Add an input field that searches for the keyword in titles of posts
	/*
	$return_string .= '<div class="input_container keyword_search"><label for="keyword">Suche</label><input type="text" name="keyword" id="keyword" placeholder="Suchbegriff..." onkeyup="nxt_afp_filter(event)" /></div>';
	*/
	
	// Select manufacturer
	if ($terms = get_terms([
		'taxonomy' => $nxt_tax,
		'orderby=name',
		'hide_empty' => false, // change me
	])) :
		$return_string .= '<div class="select_container ' . $nxt_tax . '"><label for="categoryfilter">Hersteller</label><div class="select-holder"><select class="categoryfilter" name="categoryfilter" onchange="nxt_afp_filter(event)"><option>Alle</option>';
		foreach ($terms as $term) :
			if ($_GET['hersteller'] == $term->slug) {
				$return_string .= '<option value="' . $term->term_id . '" selected>' . $term->name . '</option>'; // ID of the category as the value of an option
			} else $return_string .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
		endforeach;
		$return_string .= '</select></div> <!-- .select-holder --></div> <!-- select_container -->';
	endif;

	// Select speed
	$return_string .= '<div class="select_container emo_speed"><label for="speedfilter">Geschwindigkeit</label><div class="select-holder"><select class="speedfilter" name="speedfilter" onchange="nxt_afp_filter(event)"><option>Alle</option><option value="6">6 km/h</option><option value="15">bis 15 km/h</option><option value="16">über 15 km/h</option></select></div> <!-- .select-holder--> </div><!-- .select_container -->';

	/*
	// Prepare Filter for Tags -> we build a fieldset of checkboxes that's listing all the tags that are available
	if ($terms = get_terms(array(
		'taxonomy' => 'post_tag',
		'orderby=name',
		'hide_empty' => true,
	))) :
		$return_string .= '<div class="select_container tutorial-tag"><div class="checkbox-holder"><div class="fieldset">';
		foreach ($terms as $term) :
			if ($_GET['stichwort'] == $term->slug) {
				$return_string .= '<div class="sind-noch-klassennamen-offen-fragezeichen"><input type="checkbox" name="tagfilter[]" onclick="nxt_afp_filter()" value="' . $term->term_id . '" checked /><p>' . $term->name . '</p></div>'; // ID of the category as the value of a checkbox
			} else $return_string .= '<div class="sind-noch-klassennamen-offen-fragezeichen"><label><input type="checkbox" name="tagfilter[]" onclick="nxt_afp_filter()" value="' . $term->term_id . '" /><p>' . $term->name . '</p></label></div>';
		endforeach;
		$return_string .= '</div></div> <!-- .checkbox-holder --></div> <!-- select_container -->';
	endif;
	*/
	// We're building the default arguments for our custom WP Query
	$args = array(
		'class'			=> 'nxt_posts',
		'post_type'		=> $a["post_type"],
		'post_status'	=> $a["post_status"],
		'ppp'			=> 12,
		'orderby'		=> 'date', // we will sort posts by title
		'order'			=> 'DESC', // ASC or DESC
	);
	$return_string .= '<!-- <div class="button_container"><button>Filter anwenden</button></div> --><input type="hidden" name="action" value="nxt_afp_filter_action"></form></div><div id="response">' . nxt_post_feed($args) . '</div>';
	return $return_string;
}
add_shortcode('emo_product_filter', 'nxt_afp_filter');

// This function outputs the contents that someone filtered for on the overview page of all tutorials
function nxt_filter_function() {
	// print_r($_POST);
	global $nxt_post_type;
	global $nxt_post_status;
	$args = [
		'class'			=> 'nxt_posts',
		'post_type'		=> $nxt_post_type,
		'post_status'	=> $nxt_post_status,
		'orderby'		=> 'title', // we will sort posts by title
		'order'			=> 'DESC', // ASC or DESC
		'ppp' 			=> 12,
		's'				=> '',
		// 'paged' => 1,
	];
	// filter for categories
	if (isset($_POST['categoryfilter']) && $_POST['categoryfilter'] != 'Alle' && $_POST['categoryfilter'] != '---') {
		$args['cat'] = $_POST['categoryfilter'];
	}
	if (isset($_POST['speedfilter']) && $_POST['speedfilter'] != 'Alle' && $_POST['speedfilter'] != '---') {
		$args['speed'] = $_POST['speedfilter'];
	}
	/* // filter for tags
	if (isset($_POST['tagfilter']) && $_POST['tagfilter'] != 'Alle') {
		// $nxt_tags = implode("+", $_POST['tagfilter']);
		$args['tag__and'] = array_map('intval', $_POST['tagfilter']);
	} */
	if(isset($_POST['keyword'])) {
		$args['s'] = esc_attr($_POST['keyword']);
	}
	// var_dump($args);
	echo nxt_post_feed($args);
	die();
}
add_action('wp_ajax_nxt_afp_filter_action', 'nxt_filter_function');
add_action('wp_ajax_nopriv_nxt_afp_filter_action', 'nxt_filter_function');

/*
function data_fetch(){
	global $nxt_post_type;
	$args = [
		'posts_per_page'	=> -1,
		's'					=> esc_attr($_POST['keyword']),
		'post_type'			=> $nxt_post_type,
	];
	echo nxt_post_feed($args);
	die();
}
add_action('wp_ajax_data_fetch' , 'data_fetch');
add_action('wp_ajax_nopriv_data_fetch','data_fetch');
*/

// Output all posts
function nxt_post_feed($atts, $content = null) {
	global $nxt_post_type;
	global $nxt_tax;
	global $nxt_post_status;
	$a = shortcode_atts([
		'button' => 'yes',
		'cat' => '',
		'class' => '',
		'order' => 'DESC',
		'orderby' => 'date',
		'post_status' => $nxt_post_status,
		'post_type' => $nxt_post_type,
		'ppp' => 5,
		's' => '',
		'speed' => '',
		'tag_id' => '',
		'tag__in' => '',
		'tag__and' => '',
	], $atts);

	$feed_args = [
		'order' => $a["order"],
		'orderby' => $a["orderby"],
		'post_status' => $a["post_status"],
		'post_type' => $a["post_type"],
		'posts_per_page' => $a["ppp"],
		's' => $a["s"],
		'tag_id' => $a["tag_id"],
		'tag__in' => $a["tag__in"],
		'tag__and' => $a["tag__and"],
	];
	// Filter custom taxonomy manufacturer
	if($a["cat"] != '') {
		$feed_args['tax_query'] = [
			[
				'taxonomy'	=> $nxt_tax,
				'field'		=> 'term_taxonomy_id',
				'terms'		=> $a["cat"],
			],
		];
	}
	// Filter speed
	switch ($a["speed"]) {
		case 16:
			$comparison = '>=';
			break;
		case 15:
		case 6:
			$comparison = '<=';
			break;
	}
	if(isset($comparison)) {
		$feed_args['meta_query'] = [
			[
				'key' => 'emo_speed',
				'value' => $a["speed"],
				'compare' => $comparison,
				'type' => 'NUMERIC',
			]
		];
	}
	if(isset($_POST['post_type'])) { $feed_args['post_type'] = $_POST['post_type']; }
	$return_string = '';
	// echo '<!-- nxt debug --><pre>'; print_r($feed_args); echo '</pre>';
	$feed_query = new WP_Query($feed_args);
	if ($feed_query->have_posts()) {
		$return_string .= '<div class="nxt_afp_posts ' . $a["class"] . '">';
		while ($feed_query->have_posts()) {
			$feed_query->the_post();
			$nxt_post_id = get_the_ID();
			// Get category slugs so we can output them at the article container
			$nxt_categories = [];
			foreach (get_the_category($nxt_post_id) as $c) {
				$cat = get_category($c);
				array_push($nxt_categories, $cat->slug);
			}
			if (sizeOf($nxt_categories) > 0) {
				$post_categories = implode(' ', $nxt_categories);
			} else {
				$post_categories = 'no-category';
			}
			$nxt_permalink = get_the_permalink();
			$nxt_title = get_the_title();
			$thumbnail_url = get_the_post_thumbnail_url() ? get_the_post_thumbnail_url() : plugin_dir_url( __FILE__ ) . '/img/default.png';

			$return_string .= '<article class="nxt_afp_post ' . $post_categories . '"><a class="thumbnail_container_link" href="' . $nxt_permalink . '" title="' . $nxt_title . '"><div class="featured-image-container" style="background-image: url(' . $thumbnail_url . ');"></div></a>';
			$return_string .= '<div class="post_content"><h3 class="entry-title"><a href="' . $nxt_permalink . '" title="' . $nxt_title . '">' . $nxt_title . '</a></h3>';
			$nxt_content = (has_excerpt()) ? get_the_excerpt() : nxt_truncate(get_the_content());
			$return_string .= '<div class="post_excerpt">' . $nxt_content . '</div>';
			if($a["button"] == 'yes') {
				$return_string .= '<div class="et_pb_button_module_wrapper et_pb_module et_pb_button_alignment_"><a class="et_pb_button et_pb_module et_pb_bg_layout_dark" href="' . $nxt_permalink . '">Weiterlesen</a></div>';
			}
			$return_string .= '</div> <!-- .post_content --></article>';
		}
		wp_reset_postdata();
		$return_string .= '</div> <!-- .nxt_afp_posts -->';
	} else { 
		// echo "<!-- nxt debug - no posts found -->";
		$return_string .= '<div><h2 class="no-results entry-title">Es wurden leider keine passenden Einträge für diese Suchkriterien gefunden</h2></div>';
	}
	return $return_string;
}

// this function returns the string $text shortened to the number of words defined in the second attribute $length. It does not break words.
if (!function_exists('nxt_truncate')) {
	function nxt_truncate($text, $length = 30, $more = '...', $striptags = false) {
		if ($striptags)
			return force_balance_tags(html_entity_decode(wp_trim_words((wpautop($text)), $length, $more)));
		else
			return force_balance_tags(html_entity_decode(wp_trim_words(htmlentities(wpautop($text)), $length, $more)));
	}
};