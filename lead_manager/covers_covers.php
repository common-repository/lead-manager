<?php
class covers_covers
{
  // ATRIBUTOS ////////////////////////////////////////////////////////////////
  var $errors = array();
  var $sucesses = array();
  
  // METODOS //////////////////////////////////////////////////////////////////
  /****************************************************************************
    Set Error
  ****************************************************************************/
  function set_error($error)
  {
    array_push($this->errors, $error);
  }
  
  /****************************************************************************
    Get Error
  ****************************************************************************/
  function get_error()
  {
    if(!empty($this->errors))
    {
      print "<div style='background-color: rgb(247, 235, 207);' id='message' class='updated fade falha'>";
      foreach($this->errors as $error) print "<p>{$error}</p>";
      print "</div>";
    }
  }
  
  /****************************************************************************
    Set Sucess
  ****************************************************************************/
  function set_sucess($sucess)
  {
    array_push($this->sucesses, $sucess);
  }
  
  /****************************************************************************
    Get Sucess
  ****************************************************************************/
  function get_sucess()
  {
    if(!empty($this->sucesses))
    {
      print "<div style='background-color: rgb(207, 235, 247);' id='message' class='updated fade sucesso'>";
      foreach($this->sucesses as $sucess) print "<p>{$sucess}</p>";
      print "</div>";
    }
  }
  
  /****************************************************************************    covers_control
  ****************************************************************************/
  function covers_covers_control()
  {
    switch($_REQUEST['action'])
    {
      case 'covers_save':
        $this->covers_save();
      break;
    }
    
    $this->get_error();
    $this->get_sucess();
    
    ?>
    <div class="wrap">
      <?php $this->covers_manager(); ?>
    </div>
    <?php
  }
  
  /****************************************************************************
    Administrando
  ****************************************************************************/
  function covers_manager()
  {
    global $wpdb, $userdata;
    
    $userareas = $wpdb->get_var("SELECT cover_area_id FROM {$wpdb->covers_users} WHERE user_id = {$userdata->ID} AND cover_area_id = 0");
    
    if($userareas == "0")
      $areas = $wpdb->get_results("SELECT cover_area_id, cover_area_name, cover_area_max FROM {$wpdb->covers_areas}");
    else
      $areas = $wpdb->get_results("SELECT a.cover_area_id, a.cover_area_name, a.cover_area_max FROM {$wpdb->covers_areas} AS a, {$wpdb->covers_users} AS u WHERE u.user_id = {$userdata->ID} AND a.cover_area_id = u.cover_area_id");
    
    $cover_area_id = (!empty($_POST['cover_area_id'])) ? $_POST['cover_area_id'] : $areas[0]->cover_area_id;
    $current_area = $wpdb->get_row("SELECT cover_area_id, cover_area_max, cover_area_name FROM {$wpdb->covers_areas} WHERE cover_area_id = {$cover_area_id}");
    
    ?>
    <link type="text/css" href="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/css/covers.css" rel="stylesheet">
    <link type="text/css" href="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/css/jquery-calendar.css" rel="stylesheet">
    <link type="text/css" href="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/css/ie6.css" rel="stylesheet">
    
    <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/js/jquery.js"></script>
    <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/js/jquery-calendar.js"></script>
    <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/js/jquery-interface.js"></script>
    <script type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/js/covers.js"></script>
    
    <form action="" method="post" id="posts-filter">
      <h2><?php print __('Gerenciar Capas'); ?></h2>
      <p id="post-search">
        <select name="cover_area_id">
          <?php foreach($areas as $area) print "<option value='{$area->cover_area_id}' ".(($area->cover_area_id == $current_area->cover_area_id) ? "selected='selected'" : "").">{$area->cover_area_name}</option>"; ?>
        </select>
        <button type="submit" name="action" value="change_area" class="button"><?php _e('Filter &#187;'); ?></button>
      </p>
    </form>
    
    <h3><?php print $current_area->cover_area_name; ?></h3>
    
    <form action="" method="post">
      <input type="hidden" name="cover_area_id" id="cover_area_id" value="<?php print $current_area->cover_area_id; ?>" />
      <div class="tablenav">
        <div class="alignleft">
          <button type="submit" class="cover_ajax_save_all button-secondary"><?php print __('Save'); ?></button>
        </div>
      </div>
      <br clear="all" />
      
      <div id="covers">
        <?php $this->covers_list($current_area); ?>
      </div>
      
      <div class="tablenav">
      </div>
    </form>
    <?php
  }
  
  /****************************************************************************    covers_javascript
  ****************************************************************************/
  function covers_javascript($target, $total)
  {
    ?>
    <script type="text/javascript">
    jQuery('#post_id<?php print $target; ?>').blur(function(){
      cover_ajax_search("<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/covers_ajax.php", <?php print $target; ?>);
    });
    
    jQuery('#cover_ajax_save<?php print $target; ?>').click(function(){
      cover_ajax_save("<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/covers_ajax.php", <?php print $target; ?>);
      return false;
    });
    
    jQuery('.cover_ajax_save_all').click(function(){
      cover_ajax_save_all("<?php bloginfo('url'); ?>/wp-content/plugins/lead_manager/covers_ajax.php", <?php print $total; ?>);
      return false;
    });
    </script>
    <?php
  }
  
  /****************************************************************************
    covers_list
  ****************************************************************************/
  function covers_list($current_area)
  {
    global $wpdb;
    
    $today = date("Y-m-d H:i:s");
    $next_week = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d")+7, date("Y")));
    
    for($a = 1; $a <= $current_area->cover_area_max; $a++)
    {
      $cover = $wpdb->get_row("SELECT cover_title, cover_excerpt, cover_guid, cover_order, cover_in_date, cover_out_date, post_id, image_id FROM {$wpdb->covers} WHERE cover_in_date <= '{$today}' AND cover_out_date >= '{$today}' AND cover_order = {$a} AND cover_area_id = {$current_area->cover_area_id} ORDER BY cover_in_date DESC");
      
      if(!empty($cover))
        $thumbs = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$cover->post_id} AND post_mime_type LIKE 'image%'");
      else
        $thumbs = false;
      
      $post_title = (!empty($cover->post_id)) ? $wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = {$cover->post_id}") : "Sem Capa";
      
      $cover_in_date = (!empty($cover->cover_in_date)) ? $cover->cover_in_date : $today;
      $cover_out_date = (!empty($cover->cover_out_date)) ? $cover->cover_out_date : $next_week;
      
      $cover_in_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_in_date);
      $cover_in_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_in_date);
      
      $cover_out_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_out_date);
      $cover_out_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_out_date);
      
      ?>
      <div id="cover<?php print $a; ?>" class="cover_box">
        <span class="cover_showhide">^</span>
        <h4 id="post_title<?php print $a; ?>"><?php print $post_title; ?></h4>
        <div class="cover_details">
          <input type="hidden" name="cover_order<?php print $a; ?>" id="cover_order<?php print $a; ?>" value="<?php print $a; ?>" class="cover_order" />
          <table style="float: left;">
            <tbody>
              <tr>
                <th><label for="post_id<?php print $a; ?>">Artigo:</label></th>
                <td><input type="text" name="post_id<?php print $a; ?>" id="post_id<?php print $a; ?>" size="10" value="<?php print $cover->post_id; ?>" class="post_id" /></td>
              </tr>
              <tr>
                <th><label for="cover_title<?php print $a; ?>">T&iacute;tulo (opcional):</label></th>
                <td><input type="text" name="cover_title<?php print $a; ?>" id="cover_title<?php print $a; ?>" size="40" value="<?php print $cover->cover_title; ?>" /></td>
              </tr>
              <tr>
                <th><label for="cover_excerpt<?php print $a; ?>">Chamada (opcional):</label></th>
                <td><input type="text" name="cover_excerpt<?php print $a; ?>" id="cover_excerpt<?php print $a; ?>" size="40" value="<?php print $cover->cover_excerpt; ?>" /></td>
              </tr>
              <tr>
                <th><label for="cover_guid<?php print $a; ?>">Link (opcional):</label></th>
                <td><input type="text" name="cover_guid<?php print $a; ?>" id="cover_guid<?php print $a; ?>" size="40" value="<?php print $cover->cover_guid; ?>" /></td>
              </tr>
              <tr>
                <th><label for="cover_in_date<?php print $a; ?>">Data de Entrada:</label></th>
                <td>
                  <input type="text" name="cover_in_date<?php print $a; ?>" id="cover_in_date<?php print $a; ?>" class="jquery-calendar" size="10" value="<?php print $cover_in_date; ?>" />
                  <input type="text" name="cover_in_hour<?php print $a; ?>" id="cover_in_hour<?php print $a; ?>" size="8" value="<?php print $cover_in_hour; ?>" />
                </td>
              </tr>
              <tr>
                <th><label for="cover_out_date<?php print $a; ?>">Data de Sa&iacute;da:</label></th>
                <td>
                  <input type="text" name="cover_out_date<?php print $a; ?>" id="cover_out_date<?php print $a; ?>" class="jquery-calendar" size="10" value="<?php print $cover_out_date; ?>" />
                  <input type="text" name="cover_out_hour<?php print $a; ?>" id="cover_out_hour<?php print $a; ?>" size="8" value="<?php print $cover_out_hour; ?>" />
                </td>
              </tr>
            </tbody>
          </table>
          <div id="cover_image<?php print $a; ?>" style="float: right; padding: 0px 10px 10px 10px;">
            <h4>Imagem:</h4>
            <input type="radio" name="image_id<?php print $a; ?>" id="image_id<?php print $a; ?>" value="0" <?php print (empty($cover->image_id)) ? 'checked="checked"' : ''; ?> />Sem Imagem<br />
            <?php if(!empty($thumbs)) : ?>
            <?php foreach($thumbs as $thumb) : ?>
            <input type="radio" name="image_id<?php print $a; ?>" id="image_id<?php print $a; ?>" value="<?php print $thumb; ?>" <?php print ($thumb == $cover->image_id) ? 'checked="checked"' : ''; ?> /><?php $image = image_downsize($thumb, 'thumbnail'); print "<img src='{$image[0]}' alt='' width='75px' style='margin: 5px;' />"; ?><br />
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <br clear="all" />
          <button name="cover_ajax_save<?php print $a; ?>" id="cover_ajax_save<?php print $a; ?>" class="button"><?php print __('Save'); ?></button>
        </div>
      </div>
      <?php
      
      $this->covers_javascript($a, $current_area->cover_area_max);
    }
  }
  
  // CONSTRUTOR ///////////////////////////////////////////////////////////////
  
  // DESTRUTOR ////////////////////////////////////////////////////////////////
  
}

$covers_covers = new covers_covers();

?>
