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

$limit = 2000; //change this for processing less subs. in one step
$start = (isset($_GET['start'])) ? $_GET['start'] : 0;
$postmeta = (isset($_GET['postmeta'])) ? $_GET['postmeta'] : $wpdb->postmeta;
$comments = (isset($_GET['comments'])) ? $_GET['comments'] : $wpdb->comments;
$user_subs = $wpdb->get_results(  "SELECT * FROM $postmeta
                                  WHERE meta_key LIKE '_stcr@_%'
                                  AND meta_value NOT LIKE '%C'
                                  LIMIT $start,$limit");
$start_time = time();

foreach( $user_subs as $sub ){
  
  $post_id  = $sub->post_id;
  $comment_id = 0;
  $email = str_replace('_stcr@_','',$sub->meta_key);
  $name = '';
  $debug_value = $sub->meta_value;

  if( !preg_match( '~(.+)\|(.+)~', $sub->meta_value, $matches ) ) {
    file_put_contents('subsribers_migration.txt',"Error; $post_id; $comment_id; $email; $name; $debug_value;\n", FILE_APPEND);
    continue;
  }

  $time = strtotime( $matches[1] );
  $start_date = date( "Y-m-d H:i:s", $time - 5 );
  $end_date = date( "Y-m-d H:i:s", $time + 5 );

  // Get exact comment
  $comment = $wpdb->get_row("SELECT * FROM $comments WHERE comment_post_ID = $post_id AND comment_author_email = '$email' AND comment_date > '$start_date' AND comment_date < '$end_date' LIMIT 1");
  
  // Alternative
  if( empty( $comment ) ) {
   $comment = $wpdb->get_row("SELECT * FROM $comments WHERE comment_post_ID = $post_id AND comment_author_email = '$email' AND comment_parent = 0 LIMIT 1");
  }

  // Last chance
  if( empty( $comment ) ) {
   $comment = $wpdb->get_row("SELECT * FROM $comments WHERE comment_post_ID = $post_id AND comment_author_email = '$email'");
  }

  // try retrive name
  if( !empty($comment->comment_author) ) {
    $name = $comment->comment_author;
  }

  // get comment id if user is subscribed to replies
  if( stripos( $matches[2], 'R' ) !== false && !empty( $comment->comment_ID ) ) {
    $comment_id = $comment->comment_ID;
  }
  
  if( $comment_id ) {
    // subscribe to specific thread
    $comment_plus->subscribe_thread($post_id, $comment_id, $email, $name);
    file_put_contents('subsribers_migration.txt',"Thread; $post_id; $comment_id; $email; $name; $debug_value;\n", FILE_APPEND);
  }
  else {
    // subscribe to all replies
    $comment_plus->subscribe($post_id, $email, $name);
    file_put_contents('subsribers_migration.txt',"Comment; $post_id; $comment_id; $email; $name; $debug_value;\n", FILE_APPEND);
  }
}

$duration = time() - $start_time;

file_put_contents('subsribers_migration.txt',"####### ".date('r')." N:$start duration: $duration s #######\n", FILE_APPEND);

die('DONE');
?>