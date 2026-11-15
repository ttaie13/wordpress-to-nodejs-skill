<?php
/**
 * Plugin Name: WP Node Migration & Staging
 * Description: Read-only WordPress inventory, protected migration manifest export and staging readiness checks.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: Tariq Al-Taie
 */
if (!defined('ABSPATH')) exit;
final class WPNM_Admin {
 const NONCE = 'wpnm_action';
 public static function init() {
  add_action('admin_menu', [__CLASS__, 'menu']);
  add_action('admin_post_wpnm_export', [__CLASS__, 'export']);
  add_action('admin_post_wpnm_content', [__CLASS__, 'content_export']);
 }
 public static function menu() { add_management_page('WP Node Migration', 'WP Node Migration', 'manage_options', 'wp-node-migration', [__CLASS__, 'page']); }
 private static function inventory() {
  global $wpdb;
  require_once ABSPATH . 'wp-admin/includes/plugin.php';
  $plugins = get_plugins();
  $active = (array) get_option('active_plugins', []);
  $active_network = is_multisite() ? array_keys((array)get_site_option('active_sitewide_plugins', [])) : [];
  $theme = wp_get_theme();
  $post_types = get_post_types([], 'names');
  $counts = [];
  foreach ($post_types as $type) {
   $c = wp_count_posts($type);
   $counts[$type] = $c ? array_sum((array)$c) : 0;
  }
  $shortcodes = array_keys($GLOBALS['shortcode_tags'] ?? []);
  sort($shortcodes);
  $tables = $wpdb->get_col('SHOW TABLES');
  return [
   'generated_at'=>gmdate('c'), 'site_url'=>home_url('/'), 'wordpress_version'=>get_bloginfo('version'),
   'php_version'=>PHP_VERSION, 'multisite'=>is_multisite(),
   'theme'=>['name'=>$theme->get('Name'), 'stylesheet'=>$theme->get_stylesheet(), 'template'=>$theme->get_template(), 'version'=>$theme->get('Version')],
   'plugins'=>array_map(function($file)use($plugins,$active,$active_network){return ['file'=>$file,'name'=>$plugins[$file]['Name']??$file,'version'=>$plugins[$file]['Version']??'', 'active'=>in_array($file,$active,true)||in_array($file,$active_network,true)];},array_keys($plugins)),
   'post_type_counts'=>$counts, 'registered_shortcodes'=>$shortcodes,
   'table_names'=>array_values((array)$tables),
   'permalink_structure'=>get_option('permalink_structure'),
   'rest_api_url'=>rest_url(),
   'notes'=>['This inventory contains metadata only, not site files, content, credentials or database rows.', 'Table names and plugin identifiers may disclose architecture. Treat as confidential.']
  ];
 }
 public static function page() {
  if (!current_user_can('manage_options')) wp_die('Not allowed');
  $data=self::inventory();
  echo '<div class="wrap"><h1>WP Node Migration &amp; Staging</h1>';
  echo '<p>Read-only discovery dashboard. Production is never modified by this plugin.</p>';
  echo '<h2>Site</h2><p><strong>'.esc_html($data['site_url']).'</strong> — WordPress '.esc_html($data['wordpress_version']).' / PHP '.esc_html($data['php_version']).'</p>';
  echo '<h2>Theme</h2><p>'.esc_html($data['theme']['name']).' ('.esc_html($data['theme']['version']).')</p>';
  echo '<h2>Installed plugins</h2><table class="widefat striped"><thead><tr><th>Plugin</th><th>Version</th><th>Status</th></tr></thead><tbody>';
  foreach($data['plugins'] as $p) echo '<tr><td>'.esc_html($p['name']).'</td><td>'.esc_html($p['version']).'</td><td>'.($p['active']?'Active':'Inactive').'</td></tr>';
  echo '</tbody></table><h2>Content types</h2><ul>';
  foreach($data['post_type_counts'] as $k=>$v) echo '<li>'.esc_html($k).': '.esc_html((string)$v).'</li>';
  echo '</ul><h2>Registered shortcodes</h2><p>'.esc_html(implode(', ',$data['registered_shortcodes'])).'</p>';
  echo '<h2>Download migration manifest</h2><p>Download locally and review before sharing with Claude or GitHub.</p>';
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="wpnm_export">';
  wp_nonce_field(self::NONCE);
  submit_button('Download JSON inventory');echo '</form>';
  echo '<h2>Export content for Node.js</h2><p>Exports published public pages/posts, taxonomies, selected public metadata, and media URLs. No passwords, private posts, users or secrets are included. Review exported content before sharing.</p>';
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="wpnm_content">';
  wp_nonce_field(self::NONCE);submit_button('Download public content JSON');echo '</form>';
  echo '<h2>Staging setup</h2><p>For a full WordPress clone, use the included <code>scripts/create-wp-staging.sh</code> on a server with WP-CLI, after supplying an isolated staging path, URL and database. For Node.js staging, use the generated Next.js project on a separate deployment. This dashboard does not provision either automatically.</p>';
  echo '<p><strong>Safety:</strong> Block staging from public access; disable outbound email, payment processing, scheduled jobs and indexing. Never run staging against the production database.</p></div>';
 }

 public static function content_export() {
  if (!current_user_can('manage_options')) wp_die('Not allowed',403);
  check_admin_referer(self::NONCE);
  // Only published, publicly queryable content. Never serialize user records or arbitrary postmeta.
  $types=get_post_types(['public'=>true],'names');
  unset($types['attachment']);
  $items=[]; $page=1; $limit=20000;
  do {
   $q=new WP_Query(['post_type'=>array_values($types),'post_status'=>'publish','posts_per_page'=>100,'paged'=>$page,'orderby'=>'ID','order'=>'ASC','no_found_rows'=>false]);
   foreach($q->posts as $post) {
    if(count($items)>=$limit) wp_die('Export exceeds 20,000 posts. Use WP-CLI export for larger sites.',400);
    $terms=[];
    foreach(get_object_taxonomies($post->post_type) as $taxonomy) {
     $found=wp_get_post_terms($post->ID,$taxonomy,['fields'=>'names']);
     if(!is_wp_error($found)) $terms[$taxonomy]=$found;
    }
    $items[]=['id'=>$post->ID,'type'=>$post->post_type,'slug'=>$post->post_name,
      'url'=>get_permalink($post),'parent'=>$post->post_parent,'title'=>get_the_title($post),
      'html'=>apply_filters('the_content',$post->post_content),
      'excerpt'=>get_the_excerpt($post),'date'=>$post->post_date_gmt,
      'featured_image'=>get_the_post_thumbnail_url($post,'full')?:null,'terms'=>$terms];
   }
   $more=$page<$q->max_num_pages; wp_reset_postdata(); $page++;
  } while($more);
  $payload=['schema_version'=>1,'exported_at'=>gmdate('c'),'source'=>home_url('/'),
   'notice'=>'Public content only. HTML may contain plugin-specific markup or shortcodes requiring manual migration.',
   'items'=>$items];
  $json=wp_json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
  if(!$json) wp_die('JSON encoding failed',500);
  nocache_headers();header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="wp-node-content-'.gmdate('Ymd-His').'.json"');
  header('X-Content-Type-Options: nosniff');echo $json;exit;
 }
 public static function export() {
  if (!current_user_can('manage_options')) wp_die('Not allowed',403);
  check_admin_referer(self::NONCE);
  $json=wp_json_encode(self::inventory(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  nocache_headers();header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="wp-node-inventory-'.gmdate('Ymd-His').'.json"');
  header('X-Content-Type-Options: nosniff');echo $json;exit;
 }
}
WPNM_Admin::init();
