<?php

/* Subscribes migration from Subscribe to comments Reloaded to Comment plus
 **************************************************************************
 * Instructions:
 * 1) Put this script to wordpress main directory, where wp-load.php file is
 * 2) Go to script address in your browser
 * 3) Set GET parameter with "start" value. Scripts process only 1000 subscriptions in one step. F.E. If you have 3400 subscriptions, run script 3 times with following attributes: start=0, start=1000, start=2000, start=3000
 **************************************************************************
 * EXAMPLE of url: http://example.com/subs_migrate.php?start=1000
 * LOG FILE: All changes are logged to subsribers_migration.txt in directory with script
 */


include 'wp-load.php';

global $wpdb;
global $comment_plus;

$limit = 1000; //change this for processing less subs. in one step

$start = (isset($_GET['start'])) ? $_GET['start'] : 0;

$user_subs = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}wc_comments_subscription WHERE confirm = 1 LIMIT $start,$limit");
$start_time = time();

foreach( $user_subs as $sub ){

  $post_id = $sub->post_id;
  $comment_id = 0;
  $email = $sub->email;
  $name = $wpdb->get_var("SELECT comment_author FROM {$wpdb->comments} WHERE comment_author_email = '$email' LIMIT 1");

  if( $name == NULL ){
    $name = '';
  }
  
  $debug_value = $sub->meta_value;
  
  $comment_plus->subscribe($post_id, $email, $name);

  file_put_contents('subsribers_migration.txt',"$post_id; $email; $name; $debug_value;\n", FILE_APPEND);

}

$duration = time() - $start_time;
file_put_contents('subsribers_migration.txt',"####### ".date('r')." N:$start duration: $duration s #######\n", FILE_APPEND);

echo "All done!";

?>