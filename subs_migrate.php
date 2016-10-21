<?php
/* Subscribes migration from Subscribe to comments Reloaded to Comment plus
 **************************************************************************
 * Instructions:
 * 1) Put this script to wordpress main directory, where wp-load.php file is
 * 2) Go to script address in your browser
 * 3) Set GET parameter with "start" value. Scripts process only 1000 subscriptions in one step. F.E. If you have 3400 subscriptions, run script 3 times with following attributes: start=0, start=1000, start=2000, start=3000
 * 4) In case you run the script for multisite or you don't use standard wp_postmeta and wp_comments table names you need to set GET parameters with "postmeta" value (e.g wp_6_postmeta) and with "comments" value (e.g wp_6_comments)
 **************************************************************************
 * EXAMPLE of url: http://example.com/subs_migrate.php?start=0&postmeta=wp_6_postmeta&comments=wp_6_comments
 * LOG FILE: All changes are logged to subsribers_migration.txt in directory with script
 */
include 'wp-load.php';
global $wpdb;
global $comment_plus;
$limit = 1000; //change this for processing less subs. in one step
$start = (isset($_GET['start'])) ? $_GET['start'] : 0;
$postmeta = (isset($_GET['postmeta'])) ? $_GET['postmeta'] : 'wp_postmeta';
$comments = (isset($_GET['comments'])) ? $_GET['comments'] : 'wp_comments';
$user_subs = $wpdb->get_results(  "SELECT * FROM $postmeta 
                                  WHERE meta_key LIKE '_stcr@_%' 
                                  AND meta_value NOT LIKE '%C' 
                                  LIMIT $start,$limit");
$start_time = time();
foreach( $user_subs as $sub ){
  $post_id = $sub->post_id;
  $comment_id = 0;
  $email = str_replace('_stcr@_','',$sub->meta_key);
  $name = $wpdb->get_var("SELECT comment_author FROM $comments WHERE comment_author_email = '$email' LIMIT 1");
  
  if( $name == NULL ){
    $name = '';
  }
  
  $debug_value = $sub->meta_value;
  
  $comment_plus->subscribe($post_id, $email, $name);
  file_put_contents('subsribers_migration.txt',"$post_id; $email; $name; $debug_value;\n", FILE_APPEND);
}
$duration = time() - $start_time;
file_put_contents('subsribers_migration.txt',"####### ".date('r')." N:$start duration: $duration s #######\n", FILE_APPEND);
die('ok');
?>