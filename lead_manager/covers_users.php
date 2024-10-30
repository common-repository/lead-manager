<?php
class covers_users
{
  // ATRIBUTOS ////////////////////////////////////////////////////////////////
  var $user_id;
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
  
  /****************************************************************************    covers_users_control
  ****************************************************************************/
  function covers_users_control()
  {
    $this->user_id = (!empty($_POST['user_id'])) ? $_POST['user_id'] : 1;
    
    switch($_REQUEST['action'])
    {
      case 'covers_users_save':
        $cover_area_ids = $_POST['cover_area_ids'];
        $cover_area_default = $_POST['cover_area_default'];
        
        $this->covers_users_save($cover_area_ids, $cover_area_default);
      break;
    }
    
    $this->get_error();
    $this->get_sucess();
    
    ?>
    <div class="wrap">
      <?php $this->covers_users_table_list(); ?>
    </div>
    <?php
  }
  
  /****************************************************************************
    covers_users_save
  ****************************************************************************/
  function covers_users_save($cover_area_ids, $cover_area_default)
  {
    global $wpdb;
    
    if(is_array($cover_area_ids))
    {
      if(empty($cover_area_default))
        $cover_area_default = $cover_area_ids[0];
      
      if(!in_array($cover_area_default, $cover_area_ids))
        array_push($cover_area_ids, $cover_area_default);
      
      $allow = '';
      foreach($cover_area_ids as $cover_area_id)
      {
        if(!empty($allow)) $allow .= ", ";
        
        $allow .= "({$cover_area_id}, ".(($cover_area_id == $cover_area_default) ? 1 : 0).", {$this->user_id})";
      }
      
      $wpdb->query("DELETE FROM {$wpdb->covers_users} WHERE user_id = {$this->user_id}");
      
      if($wpdb->query("INSERT INTO {$wpdb->covers_users} (cover_area_id, cover_area_default, user_id) VALUES {$allow}"))
        $this->set_sucess("&Aacute;rea atualizada com sucesso!");
      else
        $this->set_error("Falha ao atualizar &aacute;rea!");
    }
    else
    {
      if($wpdb->query("DELETE FROM {$wpdb->covers_users} WHERE user_id = {$this->user_id}"))
        $this->set_sucess("&Aacute;rea limpada com sucesso!");
      else
        $this->set_error("Falha ao limpar &aacute;rea!");
    }
  }
  
  /****************************************************************************
    covers_users_filter
  ****************************************************************************/
  function covers_users_filter()
  {
    global $wpdb;
    
    $users = $wpdb->get_results("SELECT ID, user_login FROM {$wpdb->users}");
    $user_name = $wpdb->get_var("SELECT user_login FROM {$wpdb->users} WHERE ID = {$this->user_id}");
    
    ?>
    <form method="post" id="posts-filter">
      <h2><?php print __('Covers Limit '); ?></h2>
      <p id="post-search">
        <select name="user_id">
          <?php foreach($users as $user) print "<option value='{$user->ID}' ".(($user->ID == $this->user_id) ? "selected='selected'" : "").">{$user->user_login}</option>"; ?>
        </select>
        <button type="submit" name="action" value="change_user" class="button"><?php _e('Filter &#187;'); ?></button>
      </p>
    </form>
    
    <h3><?php print $user_name; ?></h3>
    <?php
  }
  
  /****************************************************************************
    covers_users_table_list
  ****************************************************************************/
  function covers_users_table_list()
  {
    global $wpdb;
    
    $areas = $wpdb->get_results("SELECT cover_area_id, cover_area_name FROM {$wpdb->covers_areas}");
    $covers_users = $wpdb->get_col("SELECT cover_area_id FROM {$wpdb->covers_users} WHERE user_id = {$this->user_id}");
    $covers_users['default'] = $wpdb->get_var("SELECT cover_area_id FROM {$wpdb->covers_users} WHERE cover_area_default = 1 AND user_id = {$this->user_id}");
    
    ?>
    <?php $this->covers_users_filter(); ?>
    
    <form method="post">
      <input type="hidden" name="user_id" value="<?php print $this->user_id; ?>">
      
      <div class="tablenav">
        <div class="alignleft">
          <button type="submit" name="action" value="covers_users_save" class="button-secondary"><?php print _e('Save'); ?></button>
        </div>
      </div>
      <br class="clear" />
      
      <table class="widefat">
        <thead>
          <tr>
            <th style="text-align: center;"><input type="checkbox" name="cover_area_ids[]" value="0" <?php if(in_array("0", $covers_users)) print 'checked="checked"'; ?> /></th>
            <th style="text-align: center;"><?php print __('Default'); ?></th>
            <th width="90%"><?php print __('Name'); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($areas as $area) : ?>
          <?php $alternate = !$alternate; ?>
          <tr <?php if($alternate) print 'class="alternate"'; ?>>
            <td style="text-align: center"><input type="checkbox" name="cover_area_ids[]" value="<?php print $area->cover_area_id; ?>" <?php if(in_array($area->cover_area_id, $covers_users)) print 'checked="checked"'; ?> /></td>
            <td style="text-align: center"><input type="radio" name="cover_area_default" value="<?php print $area->cover_area_id; ?>" <?php if($area->cover_area_id == $covers_users['default']) print 'checked="checked"'; ?> /></td>
            <td><?php print $area->cover_area_name; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      
      <div class="tablenav">
        <div class="alignleft">
          <button type="submit" name="action" value="covers_users_save" class="button-secondary"><?php print _e('Save'); ?></button>
        </div>
      </div>
    </form>
    <?php
  }
  
  // CONSTRUTOR ///////////////////////////////////////////////////////////////
  
  // DESTRUTOR ////////////////////////////////////////////////////////////////
  
}

$covers_users = new covers_users();

?>
