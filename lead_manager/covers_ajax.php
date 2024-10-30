<?php
require_once('../../../wp-config.php');
  
class covers_ajax
{  
  // ATRIBUTOS ////////////////////////////////////////////////////////////////
  
  // METODOS //////////////////////////////////////////////////////////////////
  /****************************************************************************
    cover_ajax_search
  ****************************************************************************/
  function cover_ajax_search()
  {
    global $wpdb;
    
    $today = date("Y-m-d H:i:s");
    $next_week = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d")+7, date("Y")));
    
    $post_id = $_POST['post_id'];
    
    $cover = $wpdb->get_row("SELECT post_id, image_id, cover_title, cover_excerpt, cover_guid, cover_in_date, cover_out_date FROM {$wpdb->covers} WHERE post_id = {$post_id}");
    
    $post = $wpdb->get_row("SELECT ID, post_title FROM {$wpdb->posts} WHERE ID = {$post_id}");
    if(empty($post->post_title)) $post->post_title = "Sem Capa";
    
    if(!empty($cover))
      $thumbs = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$cover->post_id} AND post_mime_type LIKE 'image%'");
    
    $cover_in_date = (!empty($cover->cover_in_date) && $cover->cover_in_date > $today) ? $cover->cover_in_date : $today;
    $cover_out_date = (!empty($cover->cover_out_date) && $cover->cover_out_date < $today) ? $cover->cover_out_date : $next_week;
    
    $cover_in_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_in_date);
    $cover_in_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_in_date);
    
    $cover_out_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_out_date);
    $cover_out_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_out_date);
    
    if(!empty($thumbs)) :
      foreach($thumbs as $thumb) :
        $image = image_downsize($thumb, 'thumbnail');
        $cover_image .= "<image_src>{$image[0]}</image_src>";
        $cover_image .= "<image_default>".(($thumb == $cover->image_id) ? "1" : "0")."</image_default>";
      endforeach;
    endif;
    
    print "<cover>";
    print "<post_title>{$post->post_title}</post_title>";
    print "<post_id>{$post->ID}</post_id>";
    print "<cover_title>{$cover->cover_title}</cover_title>";
    print "<cover_excerpt>{$cover->cover_excerpt}</cover_excerpt>";
    print "<cover_guid>{$cover->cover_guid}</cover_guid>";
    print "<cover_in_date>{$cover_in_date}</cover_in_date>";
    print "<cover_in_hour>{$cover_in_hour}</cover_in_hour>";
    print "<cover_out_date>{$cover_out_date}</cover_out_date>";
    print "<cover_out_hour>{$cover_out_hour}</cover_out_hour>";
    print "<cover_image>{$cover_image}</cover_image>";
    print "</cover>";
  }
  
  /****************************************************************************
    cover_ajax_save
  ****************************************************************************/
  function cover_ajax_save()
  {
    global $wpdb;
    
    $today = date("Y-m-d H:i:s");
    $next_week = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d")+7, date("Y")));
    
    $post_id = $_POST['post_id'];
    $image_id = (!empty($_POST['image_id'])) ? $_POST['image_id'] : 0;
    $cover_order = $_POST['cover_order'];
    $cover_title = $_POST['cover_title'];
    $cover_excerpt = $_POST['cover_excerpt'];
    $cover_guid = $_POST['cover_guid'];
    $cover_in_date = $_POST['cover_in_date'].' '.$_POST['cover_in_hour'];
    $cover_out_date = $_POST['cover_out_date'].' '.$_POST['cover_out_hour'];
    $cover_area_id = $_POST['cover_area_id'];
    
    $cover_in_date = (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', $cover_in_date)) ? preg_replace('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3-$2-$1 $4', $cover_in_date) : $today;
    $cover_out_date = (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', $cover_out_date)) ? preg_replace('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3-$2-$1 $4', $cover_out_date) : $next_week;
    
    if($wpdb->get_var("SELECT post_id FROM {$wpdb->covers} WHERE post_id = {$post_id}"))
    {
      if($wpdb->query("UPDATE {$wpdb->covers} SET cover_area_id = {$cover_area_id}, cover_order = {$cover_order}, cover_title = '{$cover_title}', cover_excerpt = '{$cover_excerpt}', cover_guid = '{$cover_guid}', cover_in_date = '{$cover_in_date}', cover_out_date = '{$cover_out_date}', image_id = '{$image_id}' WHERE post_id = {$post_id}"))
        $status = "ok";
      else
        $status = "fail";
    }
    else
    {
      if($wpdb->query("INSERT INTO {$wpdb->covers} (post_id, image_id, cover_order, cover_title, cover_excerpt, cover_guid, cover_in_date, cover_out_date, cover_area_id) VALUES ({$post_id}, '{$image_id}', {$cover_order}, '{$cover_title}', '{$cover_excerpt}', '{$cover_guid}', '{$cover_in_date}', '{$cover_out_date}', {$cover_area_id})"))
        $status = "ok";
      else
        $status = "fail";
    }
    
    print "<cover>";
    print "<status>{$status}</status>";
    print "</cover>";
  }
  
  // CONSTRUTOR ///////////////////////////////////////////////////////////////
  /****************************************************************************
    covers_ajax
  ****************************************************************************/
  function covers_ajax()
  {
    if(!current_user_can('Manage Covers'))
      exit();
    
    switch($_POST['action'])
    {
      case "cover_ajax_search":
        $this->cover_ajax_search();
      break;
      case "cover_ajax_save":
        $this->cover_ajax_save();
      break;
    }
  }
  
  // DESTRUTOR ////////////////////////////////////////////////////////////////
  
}

$covers_ajax = new covers_ajax();

?>
