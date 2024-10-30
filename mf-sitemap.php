<?php
/*
Plugin Name: MF Sitemap
Text Domain: mf-sitemap
Plugin URI: http://wordpress.org/plugins/mf-sitemap/
Description: MF Sitemap is a simple sitemap that works perfectly.
Author: Mjbmr
Version: 0.2
Author URI: http://mjbmr.com/
*/

/**
 * Setup for MF Sitemap.
 */
function mf_sitemap_setup() {
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	$mofile = WP_PLUGIN_DIR . "/mf-sitemap/languages/{$locale}.mo";
	load_textdomain( 'mf-sitemap', $mofile );
	search_engins_ping();
}

add_action( 'plugins_loaded', 'mf_sitemap_setup' );

add_action('admin_menu', 'mf_sitemap_plugin_menu_adder');

function search_engins_ping()
{
	$urls = array(
		"http://www.google.com/webmasters/sitemaps/ping?sitemap=",
		"http://www.bing.com/webmaster/ping.aspx?siteMap="
	);
	$permalink = get_option('permalink_structure');
	$siteurl = get_option('siteurl');
	$parts = parse_url($siteurl);
	$sitemap_url = is_ssl() ? 'https://' : 'http://';
	$sitemap_url .= $_SERVER['HTTP_HOST'];
	$sitemap_url .= $parts['path'];
	$sitemap_url .= $permalink ? '/sitemap.xml' : '/?sitemap';
	$pinged_adrs = get_option('_mf_sitemap_pinged_adrs',array());
	$old_pinged_adrs = $pinged_adrs;
	foreach($urls as $url)
	{
		if(!isset($pinged_adrs[$url]))
		{
			$pinged_adrs[$url] = array();
		}
		if(!isset($pinged_adrs[$url][$sitemap_url]))
		{
			$response = wp_remote_get( $url.urlencode($sitemap_url) );
			if( !is_wp_error( $response ) ) {
				$pinged_adrs[$url][$sitemap_url] = 1;
			}
		}
	}
	if($pinged_adrs!=$old_pinged_adrs)
	{
		update_option('_mf_sitemap_pinged_adrs', $pinged_adrs);
	}
}

function mf_sitemap_plugin_menu_adder() {
	add_plugins_page(__( 'MF Sitemap', 'mf-sitemap' ), __( 'MF Sitemap', 'mf-sitemap' ), 'manage_options', 'mf-sitemap', 'mf_sitemap_plugin_menu');
}

function mf_sitemap_plugin_menu()
{
	$taxonomies = get_taxonomies(array('show_ui'=>1),'objects');
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$items_per_page = isset($_POST['items_per_page']) ? (int)$_POST['items_per_page'] : 0;
		$items_per_page = $items_per_page == 0 ? 100 : $items_per_page;
		$priorities = isset($_POST['priority']) ? $_POST['priority'] : array('post'=>'0.9','default'=>'0.8');
		$changesfreqs = isset($_POST['changefreq']) ? $_POST['changefreq'] : array('post'=>'hourly','default'=>'hourly');
		$hide_xsl = isset($_POST['hide_xsl']) ? $_POST['hide_xsl'] : false;
		update_option('_mf_sitemap_items_per_page', $items_per_page);
		update_option('_mf_sitemap_priorities', $priorities);
		update_option('_mf_sitemap_changesfreqs', $changesfreqs);
		update_option('_mf_sitemap_hide_xsl', $hide_xsl);
		$updated = true;
	} else {
		$items_per_page = get_option('_mf_sitemap_items_per_page',100);
		$priorities = get_option('_mf_sitemap_priorities',array('post'=>'0.9','default'=>'0.8'));
		$changesfreqs = get_option('_mf_sitemap_changesfreqs',array('post'=>'hourly','default'=>'hourly'));
		$hide_xsl = get_option('_mf_sitemap_hide_xsl',false);
		$updated = false;
	}
	?><div class="wrap">
	<?php
	echo $updated ? sprintf('<div id="message" class="updated"><p><strong>%s</strong></p></div>', __('Settings saved.')) : '';
	screen_icon('plugins');
	?><h2><?php _e( 'MF Sitemap', 'mf-sitemap' ) ?></h2><br>
	<form method="POST">
	<table>
		<tr>
			<td><?php _e( 'Your sitemap:', 'mf-sitemap'); ?></td>
			<td>
			<?php
				$permalink = get_option('permalink_structure');
				$siteurl = get_option('siteurl');
				$parts = parse_url($siteurl);
				$path = $parts['path'];
				$part = $permalink ? '/sitemap.xml' : '/?sitemap';
				?><a dir="ltr" href="<?php echo $path.$part; ?>"><?php echo $part; ?></a>
			</td>
		</tr>
		<tr>
			<td><label for="items_per_page"><?php _e( 'Number of items per page:', 'mf-sitemap'); ?> </label></td>
			<td><input id="items_per_page" name="items_per_page" value="<?php echo $items_per_page; ?>"/></td>
		</tr>
		<tr>
			<td><label for="priorities"><?php _e( 'Priorities', 'mf-sitemap'); ?></label></td>
			<td id="priorities">
				<table>
					<?php
					?><tr><?php
					?><td><label for="priority_post"><?php _e( 'Posts'); ?></label></td><?php
					?><td><select id="priority_post" name="priority[post]">
					<?php 
						$priority = $priorities['post'];
						foreach(range(0,10) as $i)
						{
							$i = $i/10;
							$i = (int)$i == $i ? $i.".0" : $i;
							?><option <?php echo $priority == $i ? 'selected' : ''; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option><?php
						}
					?>
					</select></td><?php
					?></tr><?php
					foreach($taxonomies as $taxonomy_name => $taxonomy_obj)
					{
						?><tr><?php
						?><td><label for="priority_<?php echo $taxonomy_name; ?>"><?php echo $taxonomy_obj->labels->name; ?></label></td><?php
						?><td><select name="priority[<?php echo $taxonomy_name; ?>]" id="priority_<?php echo $taxonomy_name; ?>">
						<?php 
							$priority = isset($priorities[$taxonomy_name]) ? $priorities[$taxonomy_name] : $priorities['default'];
							foreach(range(0,10) as $i)
							{
								$i = $i/10;
								$i = (int)$i == $i ? $i.".0" : $i;
								?><option <?php echo $priority == $i ? 'selected' : ''; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option><?php
							}
						?>
						</select></td><?php
						?></tr><?php
					}?>
				</table>
			</td>
		</tr>
		<tr>
			<td><label for="changesfreqs"><?php _e( 'Changes frequencies', 'mf-sitemap'); ?></label></td>
			<td id="changesfreqs">
				<table>
					<?php
					?><tr><?php
					?><td><label for="changefreq_post"><?php _e( 'Posts'); ?></label></td><?php
					?><td><select id="changefreq_post" name="changefreq[post]">
					<?php
						$items = array('always','hourly','daily','weekly','monthly','yearly','never');
						$changefreq = $changesfreqs['post'];
						foreach($items as $item)
						{
							?><option <?php echo $changefreq == $item ? 'selected' : ''; ?> value="<?php echo $item; ?>"><?php _e(ucfirst($item), 'mf-sitemap'); ?></option><?php
						}
					?>
					</select></td><?php
					?></tr><?php
					foreach($taxonomies as $taxonomy_name => $taxonomy_obj)
					{
						?><tr><?php
						?><td><label for="changefreq_<?php echo $taxonomy_name; ?>"><?php echo $taxonomy_obj->labels->name; ?></label></td><?php
						?><td><select name="changefreq[<?php echo $taxonomy_name; ?>]" id="changefreq_<?php echo $taxonomy_name; ?>">
						<?php 
							$changefreq = isset($changesfreqs[$taxonomy_name]) ? $changesfreqs[$taxonomy_name] : $changesfreqs['default'];
							foreach($items as $item)
							{
								?><option <?php echo $changefreq == $item ? 'selected' : ''; ?> value="<?php echo $item; ?>"><?php _e(ucfirst($item), 'mf-sitemap'); ?></option><?php
							}
						?>
						</select></td><?php
						?></tr><?php
					}?>
				</table>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><label for="hide_xsl"><input name="hide_xsl" id="hide_xsl" type="checkbox" <?php echo $hide_xsl ? 'checked' : ''; ?> />  <?php _e('Disable Stylesheet','mf-sitemap'); ?></label></td>
		</tr>
		<tr>
			<td><br></td>
			<td><br></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" class="button-primary" value="<?php _e( 'Save Changes') ?>" /></td>
		</tr>
	</table>
	</form></div><?php
}

function mf_sitemap()
{
	$permalink = get_option('permalink_structure');
	$siteurl = get_option('siteurl');
	$parts = parse_url($siteurl);
	$path = $parts['path'];
	
	if(isset($_GET['sitemap']))
	{
		$type = isset($_GET['type']) ? $_GET['type'] : '';
		switch($type)
		{
			case 'xsl':
				mf_sitemap_xsl($permalink,$path);
				break;
			case 'css':
				mf_sitemap_style($permalink,$path);
				break;
			case 'post':
				mf_sitemap_posts($permalink,$path);
				break;
			case '':
				mf_sitemap_main($permalink,$path);
				break;
			default:
				mf_sitemap_terms($permalink,$path,$type);
		}
	}
	$uri = $_SERVER['REQUEST_URI'];
	$path_len = strlen($parts['path']);
	if(strlen($uri) > $path_len && substr($uri,0,$path_len) == $path)
	{
		$request = substr($uri,$path_len);
		$parts = parse_url($request);
		switch($parts['path'])
		{
			case '/sitemap.xml':
				mf_sitemap_main($permalink,$path);
				break;
			case '/sitemap_post.xml':
				mf_sitemap_posts($permalink,$path);
				break;
			default: 
				if(preg_match('%^/sitemap_([^?]*).xml%i', $parts['path'],$match))
				{
					mf_sitemap_terms($permalink,$path,$match[1]);
				}
		}
	}
}

add_action('parse_request', 'mf_sitemap');

function mf_sitemap_main($permalink,$path)
{
	$taxonomies = get_taxonomies(array('show_ui'=>1),'objects');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('X-Robots-Tag: noindex, follow');
	header('Content-Type: text/xml');
	$base_url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
	$base_url .= '://'. $_SERVER['HTTP_HOST'];
	$base_url .= $path;
	$out = '<?xml version="1.0" encoding="UTF-8"?>';
	$hide_xsl = get_option('_mf_sitemap_hide_xsl',false);
	if(!$hide_xsl)
	{
		$out .= '<?xml-stylesheet type="text/xsl" href="';
		$out .= $base_url . '/?sitemap&amp;type=xsl';
		$out .= '"?>';
	}
	$out .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	$last_post = get_posts(array('numberposts'=>1));
	$last_post_timestamp = strtotime($last_post[0]->post_modified_gmt);
	if($last_post!=array())
	{
		$out .= '<sitemap>';
		$out .= '<loc>'.$base_url. ($permalink ? '/sitemap_post.xml' : '/?sitemap&amp;type=post') .'</loc>';
		$out .= '<lastmod>'.date('c',$last_post_timestamp).'</lastmod>';
		$out .= '</sitemap>';
	}
	foreach(array_keys($taxonomies) as $taxonomy)
	{
		$last_term = get_terms($taxonomy,array('number'=>1));
		$last_obj_in_term = get_objects_in_term($last_term[0]->term_id,$taxonomy);
		$last_post = get_post($last_obj_in_term[0]);
		$last_post_timestamp = strtotime($last_post->post_modified_gmt);
		if($last_post!=array())
		{
			$out .= '<sitemap>';
			$out .= '<loc>'.$base_url.($permalink ? '/sitemap_' .  $taxonomy . '.xml' : '/?sitemap&amp;type=' . $taxonomy ) . '</loc>';
			$out .= '<lastmod>'.date('c',$last_post_timestamp).'</lastmod>';
			$out .= '</sitemap>';
		}
	}
	$out .= '</sitemapindex>';
	$etag = '"'.dechex(crc32($out)).'"';
	$Last_Modified_timestamp = max($last_post_timestamp,0);
	$Last_Modified = date("D, d M Y H:i:s ", $Last_Modified_timestamp).'GMT';
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $Last_Modified)
	{
		if(!isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			header('HTTP/1.1 304 Not Modified');
			exit;
		} elseif($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified');
			exit;
		}
	}
	header('Last-Modified: ' . $Last_Modified);
	header('Etag: ' . $etag);
	$expires  = date("D, d M Y H:i:s ", time()).'GMT';
	header('Expires: ' . $expires);
	header('Content-Length: ' . strlen($out));
	echo $out;
	exit;
}


function mf_sitemap_posts($permalink,$path)
{
	$per_page = get_option('_mf_sitemap_items_per_page',100);
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('X-Robots-Tag: noindex, follow');
	header('Content-Type: text/xml');
	$base_url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
	$base_url .= '://'. $_SERVER['HTTP_HOST'];
	$base_url .= $path;
	$out = '<?xml version="1.0" encoding="UTF-8"?>';
	$hide_xsl = get_option('_mf_sitemap_hide_xsl',false);
	if(!$hide_xsl)
	{
		$out .= '<?xml-stylesheet type="text/xsl" href="';
		$out .= $base_url . '/?sitemap&amp;type=xsl';
		$out .= '"?>';
	}
	if(!isset($_GET['page']))
	{
		$posts_count = wp_count_posts()->publish;
		$pages = ($posts_count / $per_page);
		if(($pages - (int)$pages)!=0) $pages = ((int)$pages) + 1;
		$Last_Modified_timestamp = 0;
		$out .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach(range(1,$pages) as $i)
		{
			$last_post = get_posts(array('numberposts'=>1,'offset'=>($i-1)*$per_page));
			$last_post_timestamp = strtotime($last_post[0]->post_modified_gmt);
			if ($i == 1) $Last_Modified_timestamp = $last_post_timestamp;
			$out .= '<sitemap>';
			$out .= '<loc>' . $base_url . ( $permalink ? '/sitemap_post.xml?page=' . $i : '/?sitemap&amp;type=post&amp;page=' . $i);
			$out .= '</loc>';
			$out .= '<lastmod>'.date('c',$last_post_timestamp).'</lastmod>';
			$out .= '</sitemap>';
		}
		$out .= '</sitemapindex>';
	} else {
		$posts = get_posts(array('numberposts'=>$per_page,'offset'=>(((int)$_GET['page'])-1)*$per_page));
		$Last_Modified_timestamp = 0;
		$out .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$i = -1;
		$priorities = get_option('_mf_sitemap_priorities',array('post'=>'0.9'));
		$changesfreqs = get_option('_mf_sitemap_changesfreqs',array('post'=>'hourly'));
		foreach($posts as $post)
		{
			$i++;
			$last_post_timestamp = strtotime($post->post_modified_gmt);
			if ($i == 0) $Last_Modified_timestamp = $last_post_timestamp;
			$out .= '<url>';
			$link = get_permalink($post->ID);
			$parts = parse_url($link);
			$url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
			$url .= '://'. $_SERVER['HTTP_HOST'];
			$url .= $parts['path'];
			$url .= isset($parts['query']) ? '?' . $parts['query'] : '';
			$out .= '<loc>'.$url.'</loc>';
			$out .= '<lastmod>'.date('c',$last_post_timestamp).'</lastmod>';
			$out .= '<changefreq>' . $changesfreqs['post'] . '</changefreq>';
			$out .= '<priority>' . $priorities['post'] . '</priority>';
			$out .= '</url>';
		}
		$out .= '</urlset>';
	}
	$etag = '"'.dechex(crc32($out)).'"';
	$Last_Modified = date("D, d M Y H:i:s ", $Last_Modified_timestamp).'GMT';
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $Last_Modified)
	{
		if(!isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			header('HTTP/1.1 304 Not Modified');
			exit;
		} elseif($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified');
			exit;
		}
	}
	header('Last-Modified: ' . $Last_Modified);
	header('Etag: ' . $etag);
	$expires  = date("D, d M Y H:i:s ", time()).'GMT';
	header('Expires: ' . $expires);
	header('Content-Length: ' . strlen($out));
	echo $out;
	exit;
}

function mf_sitemap_terms($permalink,$path,$taxonomy)
{
	$taxonomies = get_taxonomies(array('show_ui'=>1),'objects');
	if(in_array($taxonomy,array_keys($taxonomies)))
	{
		$per_page = get_option('_mf_sitemap_items_per_page',100);
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('X-Robots-Tag: noindex, follow');
		header('Content-Type: text/xml');
		$base_url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
		$base_url .= '://'. $_SERVER['HTTP_HOST'];
		$base_url .= $path;
		$out = '<?xml version="1.0" encoding="UTF-8"?>';
		$hide_xsl = get_option('_mf_sitemap_hide_xsl',false);
		if(!$hide_xsl)
		{
			$out .= '<?xml-stylesheet type="text/xsl" href="';
			$out .= $base_url . '/?sitemap&amp;type=xsl';
			$out .= '"?>';
		}
		if(!isset($_GET['page']))
		{
			$out .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			$count = wp_count_terms($taxonomy);
			$pages = ($count / $per_page);
			if(($pages - (int)$pages)!=0) $pages = ((int)$pages) + 1;
			$Last_Modified_timestamp = 0;
			foreach(range(1,$pages) as $i)
			{
				
				$last_term = get_terms($taxonomy,array('number'=>1,'offset'=>($i-1)*$per_page));
				$obj_in_term = get_objects_in_term($last_term[0]->term_id,$taxonomy);
				$last_post = get_post($obj_in_term[0]);
				$last_post_timestamp = strtotime($last_post->post_modified_gmt);
				if ($i == 1) $Last_Modified_timestamp = $last_post_timestamp;
				$out .= '<sitemap>';
				$out .= '<loc>' . $base_url . ($permalink ? '/sitemap_' . $taxonomy . '.xml?page=' . $i : '/?sitemap&amp;type=' . $taxonomy . '&amp;page=' . $i);
				$out .= '</loc>';
				$out .= '<lastmod>'.date('c',$last_post_timestamp).'</lastmod>';
				$out .= '</sitemap>';
			}
			$out .= '</sitemapindex>';
		} else {
			$out .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			$terms = get_terms($taxonomy,array('number'=>$per_page,'offset'=>(((int)$_GET['page'])-1)*$per_page));
			$priorities = get_option('_mf_sitemap_priorities',array('default'=>'0.8'));
			$priority = isset($priorities[$taxonomy]) ? $priorities[$taxonomy] : $priorities['default'];
			$changesfreqs = get_option('_mf_sitemap_changesfreqs',array('default'=>'hourly'));
			$changefreq = isset($changesfreqs[$taxonomy]) ? $changesfreqs[$taxonomy] : $changesfreqs['default'];
			foreach($terms as $term)
			{
				$out .= '<url>';
				$obj_in_term = get_objects_in_term($term->term_id,$taxonomy);
				$post = get_post($obj_in_term[0]);
				$post_timestamp = strtotime($post->post_modified_gmt);
				$termlink = get_term_link((int)$term->term_id,$taxonomy);
				$parts = parse_url($termlink);
				$url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
				$url .= '://'. $_SERVER['HTTP_HOST'];
				$url .= $parts['path'];
				$url .= isset($parts['query']) ? '?' . $parts['query'] : '';
				$out .= '<loc>'.$url.'</loc>';
				$out .= '<lastmod>'.date('c',$post_timestamp).'</lastmod>';
				$out .= '<changefreq>' . $changefreq . '</changefreq>';
				$out .= '<priority>' . $priority  . '</priority>';
				$out .= '</url>';
			}
			$out .= '</urlset>';
		}
		$etag = '"'.dechex(crc32($out)).'"';
		$Last_Modified = date("D, d M Y H:i:s ", $Last_Modified_timestamp).'GMT';
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $Last_Modified)
		{
			if(!isset($_SERVER['HTTP_IF_NONE_MATCH']))
			{
				header('HTTP/1.1 304 Not Modified');
				exit;
			} elseif($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		}
		header('Last-Modified: ' . $Last_Modified);
		header('Etag: ' . $etag);
		$expires  = date("D, d M Y H:i:s ", time()).'GMT';
		header('Expires: ' . $expires);
		header('Content-Length: ' . strlen($out));
		echo $out;
		exit;
	}
}


function mf_sitemap_xsl($permalink,$path)
{
	header('X-Robots-Tag: noindex, follow');
	header('Content-Type: text/xsl');
	$base_url = (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") ? 'https' : 'http';
	$base_url .= '://'. $_SERVER['HTTP_HOST'];
	$base_url .= $path;
	$css_url = $base_url . '/?sitemap&amp;type=css';
	$out = '<?xml version="1.0" encoding="UTF-8"?>';
	$out .= '<xsl:stylesheet version="2.0"';
	$out .= ' xmlns:html="http://www.w3.org/TR/REC-html40"';
	$out .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
	$out .= ' xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"';
	$out .= ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">';
	$out .= '<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>';
	$out .= '<xsl:template match="/">';
	$out .= '<html xmlns="http://www.w3.org/1999/xhtml" dir="' . get_bloginfo('text_direction') .'" lang="' . get_bloginfo('language') . '">';
	$out .= '<head>';
	$out .= '<title></title>';
	$out .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	$out .= '<link rel="stylesheet" type="text/css" href="' .$css_url. '" />';
	$out .= '</head>';
	$out .= '<body id="index" class="home">';
	$out .= '<div class="container">';
	$out .= '<div class="row">';
	$out .= '<p></p>';
	$out .= '<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &gt; 0">';
	$out .= '<h4 class="lead">' . sprintf(__('Sitemap: %s','mf-sitemap'),'<xsl:value-of select="count(sitemap:sitemapindex/sitemap:sitemap)"/>') . '</h4>';
	$out .= '<table class="striped rounded">';
	$out .= '<thead>';
	$out .= '<tr>';
	$out .= '<th>' . __('Sitemap','mf-sitemap') . '</th>';
	$out .= '<th>' . __('Last Modified','mf-sitemap') . '</th>';
	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';
	$out .= '<xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">';
	$out .= '<tr>';
	$out .= '<td>';
	$out .= '<xsl:element name="a">';
	$out .= '<xsl:attribute name="href">';
	$out .= '<xsl:value-of select="sitemap:loc"/>';
	$out .= '</xsl:attribute>';
	$out .= '<xsl:value-of select="sitemap:loc"/>';
	$out .= '</xsl:element>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<xsl:value-of select="sitemap:lastmod"/>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '</xsl:for-each>';
	$out .= '</tbody>';
	$out .= '</table>';
	$out .= '</xsl:if>';
	$out .= '<xsl:if test="count(sitemap:sitemapindex/sitemap:sitemap) &lt; 1">';
	$out .= '<h4 class="lead">' . sprintf(__('URLs: %s','mf-sitemap'),'<xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>') . '</h4>';
	$out .= '<table class="striped rounded">';
	$out .= '<thead>';
	$out .= '<tr>';
	$out .= '<th>' . __('URL','mf-sitemap') . '</th>';
	$out .= '<th>' . __('Priority','mf-sitemap') . '</th>';
	$out .= '<th>' . __('Images','mf-sitemap') . '</th>';
	$out .= '<th>' . __('Change Frequency','mf-sitemap') . '</th>';
	$out .= '<th>' . __('Last Modified','mf-sitemap') . '</th>';
	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';
	$out .= '<xsl:for-each select="sitemap:urlset/sitemap:url">';
	$out .= '<tr>';
	$out .= '<td>';
	$out .= '<xsl:element name="a">';
	$out .= '<xsl:attribute name="href">';
	$out .= '<xsl:value-of select="sitemap:loc"/>';
	$out .= '</xsl:attribute>';
	$out .= '<xsl:attribute name="class">urls</xsl:attribute>';
	$out .= '<xsl:value-of select="sitemap:loc"/>';
	$out .= '</xsl:element>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<xsl:value-of select="sitemap:priority"/>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<xsl:value-of select="count(image:image)"/>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<xsl:value-of select="sitemap:changefreq"/>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<xsl:value-of select="sitemap:lastmod"/>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '</xsl:for-each>';
	$out .= '</tbody>';
	$out .= '</table>';
	$out .= '</xsl:if>';
	$out .= '<div class="footer">';
	$out .= '<a href="http://wordpress.org/plugins/mf-sitemap/">' . __('MF Sitemap','mf-sitemap') . '</a> ' . __('By','mf-sitemap') . ' <a href="http://mjbmr.com/">Mjbmr</a>';
	$out .= '</div>';
	$out .= '</div>';
	$out .= '</div>';
	$out .= '<script><![CDATA[';
	$out .= 'var urls = document.getElementsByClassName("urls");';
	$out .= 'for(var i = 0; i < urls.length; i++)';
	$out .= '{';
	$out .= 'url = urls[i];';
	$out .= 'url.innerHTML = decodeURIComponent(url.innerHTML);';
	$out .= '}';
	$out .= ']]></script>';
	$out .= '</body>';
	$out .= '</html>';
	$out .= '</xsl:template>';
	$out .= '</xsl:stylesheet>';
	$etag = '"'.dechex(crc32($out)).'"';
	$Last_Modified = date("D, d M Y H:i:s ",filemtime(__FILE__)).'GMT';
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $Last_Modified)
	{
		if(!isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			header('HTTP/1.1 304 Not Modified');
			exit;
		} elseif($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified');
			exit;
		}
	}
	header('Last-Modified: ' . $Last_Modified);
	header('Etag: ' . $etag);
	$expires  = date("D, d M Y H:i:s ", strtotime('+1 day')).'GMT';
	header('Expires: ' . $expires);
	header('Content-Length: ' . strlen($out));
	echo $out;
	exit;
}

function mf_sitemap_style($permalink,$path)
{
	$out = file_get_contents(WP_PLUGIN_DIR . "/mf-sitemap/gumby.css");
	if(is_rtl())
	{
		$out = str_replace("left","lleefftt",$out);
		$out = str_replace("right","left",$out);
		$out = str_replace("lleefftt","right",$out);
	}
	header('X-Robots-Tag: noindex, follow');
	header('Content-Type: text/css');
	$etag = '"'.dechex(crc32($out)).'"';
	$Last_Modified = date("D, d M Y H:i:s ",filemtime(__FILE__)).'GMT';
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $Last_Modified)
	{
		if(!isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			header('HTTP/1.1 304 Not Modified');
			exit;
		} elseif($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
			header('HTTP/1.1 304 Not Modified');
			exit;
		}
	}
	header('Last-Modified: ' . $Last_Modified);
	header('Etag: ' . $etag);
	$expires  = date("D, d M Y H:i:s ", strtotime('+1 day')).'GMT';
	header('Expires: ' . $expires);
	if(extension_loaded('zlib'))
	{
		$supported_encodings = array_map('trim',explode(',',$_SERVER['HTTP_ACCEPT_ENCODING']));
		if(in_array('gzip',$supported_encodings))
		{
			$gzip_out = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
			$gzip_out .= substr(gzcompress($out, 9), 0, - 4);
			$gzip_out .=  pack('V', crc32($out));
			$gzip_out .=  pack('V', strlen($out));
			header('Content-Encoding: gzip');
			header('Content-Length: ' . strlen($gzip_out));
			echo $gzip_out;
		} else {
			header('Content-Length: ' . strlen($out));
			echo $out;
		}
	} else {
		header('Content-Length: ' . strlen($out));
		echo $out;
	}
	exit;
}