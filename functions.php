function add_divi_tutorials_scripts() {
	wp_register_script('divi-tutorials-scripts', get_stylesheet_directory_uri() . '/js/divi-tutorials-scripts.js', false, '', true);
	wp_enqueue_script('divi-tutorials-scripts');
}
add_action('wp_enqueue_scripts', 'add_divi_tutorials_scripts', 100);


/* Filter Function for tutorials */
// This function creates 1) a search box 2) the default arguments for our custom query and it then calls another function to output the posts on our site
function nxt_tutorial_filter_shortcode($atts = null, $content = null) {
	$return_string = '';
	$a = shortcode_atts([
		'post_type' => 'post',
	], $atts);
	if($a["post_type"] == "post") {
		$return_string .= '<div id="filter_container" class="magic_selects"><form action="' . site_url() . '/wp-admin/admin-ajax.php" method="POST" id="filter" class="karmachameleon">';
		/* if($a["post_type"] != 'post') {
			foreach($a["post_type"] as $nxt_post_type) {
				$return_string .= '<input type="text" name="post_type[]" value="' . $nxt_post_type . '" style="display: none;" />';
			}
		} */
		// Add an input field that searches for the keyword in titles of posts
		$return_string .= '<div class="input_container keyword_search"><label for="keyword">Suche</label><input type="text" name="keyword" id="keyword" placeholder="Suchbegriff..." onkeyup="nxt_tut_filter()" /></div>';
		// Build a select that lists all the categories (that are not empty)
		if ($terms = get_terms(array(
			'taxonomy' => 'category',
			'orderby=name',
			'hide_empty' => true,
		))) :
			$return_string .= '<div class="select_container tutorial-category"><label for="categoryfilter">Kategorie</label><div class="select-holder"><select class="categoryfilter" name="categoryfilter" onchange="nxt_tut_filter()"><option>Alle</option>';
			foreach ($terms as $term) :
				if ($_GET['kategorie'] == $term->slug) {
					$return_string .= '<option value="' . $term->term_id . '" selected>' . $term->name . '</option>'; // ID of the category as the value of an option
				} else $return_string .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
			endforeach;
			$return_string .= '</select></div> <!-- .select-holder --></div> <!-- select_container -->';
		endif;

		// Prepare Filter for Tags -> we build a fieldset of checkboxes that's listing all the tags that are available
		if ($terms = get_terms(array(
			'taxonomy' => 'post_tag',
			'orderby=name',
			'hide_empty' => true,
		))) :
			$return_string .= '<div class="select_container tutorial-tag"><div class="checkbox-holder"><div class="fieldset">';
			foreach ($terms as $term) :
				if ($_GET['stichwort'] == $term->slug) {
					$return_string .= '<div class="sind-noch-klassennamen-offen-fragezeichen"><input type="checkbox" name="tagfilter[]" onclick="nxt_tut_filter()" value="' . $term->term_id . '" checked /><p>' . $term->name . '</p></div>'; // ID of the category as the value of a checkbox
				} else $return_string .= '<div class="sind-noch-klassennamen-offen-fragezeichen"><label><input type="checkbox" name="tagfilter[]" onclick="nxt_tut_filter()" value="' . $term->term_id . '" /><p>' . $term->name . '</p></label></div>';
			endforeach;
			$return_string .= '</div></div> <!-- .checkbox-holder --></div> <!-- select_container -->';
		endif;
	} // end filter prep for posts

	// We're building the default arguments for our custom WP Query
	$args = array(
		'class'			=> 'phpStorm',
		'post_type'		=> $a["post_type"],
		'post_status'	=> 'publish',
		'ppp'			=> 12,
		'orderby'		=> 'date', // we will sort posts by title
		'order'			=> 'DESC', // ASC or DESC
	);
	$return_string .= '<!-- <div class="button_container"><button>Filter anwenden</button></div> --><input type="hidden" name="action" value="myfilter"></form></div><div id="response">' . nxt_feed_function($args) . '</div>';
	return $return_string;
}
/*
function nxt_megafilter_shortcode() {
	$args['post_type'] = ['post', 'page', 'dwqa-question'];
	return nxt_tutorial_filter_shortcode($args);
}
add_shortcode('nxt_megafilter', 'nxt_megafilter_shortcode');
*/
// This function outputs the contents that someone filtered for on the overview page of all tutorials
function nxt_filter_function() {
	// print_r($_POST);
	$args = [
		'class'			=> 'phpStorm',
		'post_type'		=> 'post',
		'post_status'	=> 'publish',
		'orderby'		=> 'title', // we will sort posts by title
		'order'			=> 'DESC', // ASC or DESC
		'ppp' 			=> 12,
		's'				=> '',
		// 'paged' => 1,
	];
	// filter for categories
	if (isset($_POST['categoryfilter']) && $_POST['categoryfilter'] != 'Alle') {
		$args['cat'] = $_POST['categoryfilter'];
	}
	// filter for tags
	if (isset($_POST['tagfilter']) && $_POST['tagfilter'] != 'Alle') {
		// $nxt_tags = implode("+", $_POST['tagfilter']);
		$args['tag__and'] = array_map('intval', $_POST['tagfilter']);
	}
	if(isset($_POST['keyword'])) {
		$args['s'] = esc_attr($_POST['keyword']);
	}
	if(isset($_POST['post_type'])) {
		$args['post_type'] = $_POST['post_type'];
	}
	// var_dump($args);
	echo nxt_feed_function($args);
	die();
}
add_action('wp_ajax_myfilter', 'nxt_filter_function');
add_action('wp_ajax_nopriv_myfilter', 'nxt_filter_function');

function data_fetch(){
	$args = [
		'posts_per_page'	=> -1,
		's'					=> esc_attr($_POST['keyword']),
		'post_type'			=> 'post',
	];
	echo nxt_feed_function($args);
	die();
}
add_action('wp_ajax_data_fetch' , 'data_fetch');
add_action('wp_ajax_nopriv_data_fetch','data_fetch');

// Output a simple blog post feed (list all tutorials)
function nxt_feed_function($atts, $content = null) {
	$a = shortcode_atts([
		'button' => 'yes',
		'cat' => '',
		'class' => '',
		'order' => 'DESC',
		'orderby' => 'date',
		'post_status' => 'publish',
		'post_type' => 'post',
		'ppp' => 5,
		's' => '',
		'tag_id' => '',
		'tag__in' => '',
		'tag__and' => '',
	], $atts);

	$feed_args = [
		'cat' => $a["cat"],
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
	if(isset($_POST['post_type'])) { $feed_args['post_type'] = $_POST['post_type']; }
	$return_string = '';
	// print_r($feed_args);
	$feed_query = new WP_Query($feed_args);
	if ($feed_query->have_posts()) {
		$return_string .= '<div class="trendy-eistee ' . $a["class"] . '">';
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
			$return_string .= '<article class="nxt_post_infini_scroll ' . $post_categories . '"><a class="thumbnail_container_link" href="' . $nxt_permalink . '" title="' . $nxt_title . '"><div class="featured-image-container" style="background-image: url(' . get_the_post_thumbnail_url() . ');"></div></a>';
			$return_string .= '<div class="post_content"><h3 class="entry-title"><a href="' . $nxt_permalink . '" title="' . $nxt_title . '">' . $nxt_title . '</a></h3>';
			$nxt_content = (has_excerpt()) ? get_the_excerpt() : nxt_truncate(get_the_content());
			$return_string .= '<div class="post_excerpt">' . $nxt_content . '</div>';
			if($a["button"] == 'yes') {
				$return_string .= '<div class="et_pb_button_module_wrapper et_pb_module et_pb_button_alignment_"><a class="et_pb_button et_pb_module et_pb_bg_layout_dark" href="' . $nxt_permalink . '">Weiterlesen</a></div>';
			}
			$return_string .= '</div> <!-- .post_content --></article>';
		}
		wp_reset_postdata();
		$return_string .= '</div> <!-- .trendy-eistee -->';
	} else { 
		// echo "<!-- nxt debug - no posts found -->";
		$return_string .= '<div><h2 class="no-results entry-title">Es wurden leider keine Tutorials für diese Suchkriterien gefunden</h2></div>';
	}
	return $return_string;
}
