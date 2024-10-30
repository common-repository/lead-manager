<?php
/*
  Plugin Name: Lead Manager
  Plugin URI: http://xemele.cultura.gov.br/web/gerenciador-de-capas
  Description: Gerenciamento da ordem das notícias por prioridade
  Author: Equipe WebMinC
  Version: beta 0.4
  Author URI: http://xemele.cultura.gov.br
  
  Copyright (C) 2008 Equipe WebMinC
  
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.
  
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
*/

class Covers
{
  // ATRIBUTOS ////////////////////////////////////////////////////////////////
  var $covers = array();
  var $area_id;
  var $area_max;
  var $area_count;
  
  // METODOS //////////////////////////////////////////////////////////////////
  /****************************************************************************
    Definindo as Tabelas
  ****************************************************************************/
  function set_tables()
  {
    global $wpdb, $userdata;
    
    $wpdb->covers = "{$wpdb->prefix}covers";
    $wpdb->covers_areas = "{$wpdb->prefix}covers_areas";
    $wpdb->covers_users = "{$wpdb->prefix}covers_users";
  }
  
  /****************************************************************************
    Instalando
  ****************************************************************************/
  function covers_install()
  {
    global $wpdb, $wp_roles;
    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->covers_areas}'") !== $wpdb->covers_areas)
    {
      $sql = "
      CREATE TABLE {$wpdb->covers_areas}
      (
        cover_area_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        cover_area_max INTEGER UNSIGNED NOT NULL,
        cover_area_name VARCHAR(255) NOT NULL,
        
        PRIMARY KEY(cover_area_id)
      )";
      
      $wpdb->query($sql);
      
      $wpdb->query("INSERT INTO {$wpdb->covers_areas} (cover_area_max, cover_area_name) VALUES (5, 'Home')");
    }
    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->covers_users}'") !== $wpdb->covers_users)
    {
      $sql = "
      CREATE TABLE {$wpdb->covers_users}
      (
        cover_user_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        cover_area_id INTEGER UNSIGNED NOT NULL,
        cover_area_default INTEGER UNSIGNED NOT NULL,
        user_id INTEGER UNSIGNED NOT NULL,
        
        PRIMARY KEY(cover_user_id)
      )";
      
      $wpdb->query($sql);
      
      $wpdb->query("INSERT INTO {$wpdb->covers_users} (cover_area_id, cover_area_default, user_id) VALUES (0, 0, 1)");
    }
    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->covers}'") !== $wpdb->covers)
    {
      $sql = "
      CREATE TABLE {$wpdb->covers}
      (
        cover_title VARCHAR(255) NULL,
        cover_excerpt VARCHAR(255) NULL,
        cover_guid VARCHAR(255) NULL,
        cover_order INTEGER UNSIGNED NOT NULL,
        cover_in_date DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        cover_out_date DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        cover_area_id INTEGER UNSIGNED NOT NULL,
        post_id INTEGER UNSIGNED NOT NULL,
        image_id INTEGER UNSIGNED NULL,
        
        PRIMARY KEY(post_id)
      )";
      
      $wpdb->query($sql);
    }
    
    $role = get_role("administrator");
    $role->add_cap("Manage Covers");
    $role->add_cap("Manage Covers Areas");
    $role->add_cap("Manage Covers Users");
    
    $role = get_role("editor");
    $role->add_cap("Manage Covers");
    $role->add_cap("Manage Covers Areas");
    $role->add_cap("Manage Covers Users");
    
    $role = get_role("author");
    $role->add_cap("Manage Covers");
  }
  
  /****************************************************************************
    Desistalando
  ****************************************************************************/
  function covers_uninstall()
  {
    global $wpdb, $wp_roles;
    
    $wpdb->query("DROP TABLES {$wpdb->covers_users}, {$wpdb->covers_areas}, {$wpdb->covers}");
    
    foreach($wp_roles->role_names as $role => $rolename)
    {
      $wp_roles->role_objects[$role]->remove_cap("Manage Covers");
      $wp_roles->role_objects[$role]->remove_cap("Manage Covers Areas");
      $wp_roles->role_objects[$role]->remove_cap("Manage Covers Users");
    }
  }
  
  /****************************************************************************
    Menu
  ****************************************************************************/
  function covers_menu()
  {
    add_menu_page("Capas", "Capas", "Manage Covers", __FILE__, array(&$this, "covers_show"));
    add_submenu_page(__FILE__, "Áreas", "Áreas", "Manage Covers Areas", "covers-areas", array(&$this, "covers_show"));
    add_submenu_page(__FILE__, "Usuários", "Usuários", "Manage Covers Users", "covers-users", array(&$this, "covers_show"));
  }
  
  /****************************************************************************
    Painel
  ****************************************************************************/
  function covers_show()
  {
    switch($_GET["page"])
    {
      case "covers-areas":
        require(dirname(__FILE__)."/covers_areas.php");
        $covers_areas->covers_areas_control();
        break;
      case "covers-users":
        require(dirname(__FILE__)."/covers_users.php");
        $covers_users->covers_users_control();
        break;
      default:
        require(dirname(__FILE__)."/covers_covers.php");
        $covers_covers->covers_covers_control();
        break;
    }
  }
  
  /****************************************************************************
    covers_edit
  ****************************************************************************/
  function covers_edit()
  {
    if(!current_user_can("Manage Covers"))
      return false;
    
    global $wpdb, $userdata;
    
    $post_id = $_GET["post"];
    
    $today = date("Y-m-d H:i:s");
    $next_week = date("Y-m-d H:i:s", mktime(date("H"), date("i"), date("s"), date("m"), date("d")+7, date("Y")));
    
    $userareas = $wpdb->get_var("SELECT cover_area_id FROM {$wpdb->covers_users} WHERE user_id = {$userdata->ID} AND cover_area_id = 0");
    
    if($userareas == "0")
      $areas = $wpdb->get_results("SELECT cover_area_id, cover_area_name, cover_area_max FROM {$wpdb->covers_areas}");
    else
      $areas = $wpdb->get_results("SELECT a.cover_area_id, a.cover_area_name, a.cover_area_max FROM {$wpdb->covers_areas} AS a, {$wpdb->covers_users} AS u WHERE u.user_id = {$userdata->ID} AND a.cover_area_id = u.cover_area_id");
    
    if(!empty($post_id))
    {
      $cover = $wpdb->get_row("SELECT cover_title, cover_excerpt, cover_guid, cover_order, cover_in_date, cover_out_date, cover_area_id, post_id, image_id FROM {$wpdb->covers} WHERE post_id = {$post_id}");
      $thumbs = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$post_id} AND post_mime_type LIKE 'image%'");
    }
    
    $cover_in_date = (!empty($cover->cover_in_date)) ? $cover->cover_in_date : $today;
    $cover_out_date = (!empty($cover->cover_out_date)) ? $cover->cover_out_date : $next_week;
    
    $cover_in_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_in_date);
    $cover_in_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_in_date);
    
    $cover_out_hour = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$4', $cover_out_date);
    $cover_out_date = preg_replace('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3/$2/$1', $cover_out_date);
    
    if(!$areas)
      return false;
    
    ?>
    
    <div id="coversdiv" class="postbox">
      <h3><a class="togbox">+</a> Capas</h3>
      <div class="inside">
        <table style="float: left;">
          <tbody>
            <tr>
              <th style="text-align: right; vertical-align: top;"><label for="cover_area_id">Area:</label></th>
              <td>
                <?php foreach($areas as $area) : ?>
                <input type="radio" name="cover_area_id" id="cover_area_id" value="<?php print $area->cover_area_id; ?>" <?php print ($area->cover_area_id == $cover->cover_area_id) ? 'checked="checked"' : ''; ?> /><?php print $area->cover_area_name; ?><br />
                <?php endforeach; ?>
              </td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_title">T&iacute;tulo (opcional):</label></th>
              <td><input type="text" name="cover_title" id="cover_title" size="40" value="<?php print $cover->cover_title; ?>" /></td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_excerpt">Chamada (opcional):</label></th>
              <td><input type="text" name="cover_excerpt" id="cover_excerpt" size="40" value="<?php print $cover->cover_excerpt; ?>" /></td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_guid">Link (opcional):</label></th>
              <td><input type="text" name="cover_guid" id="cover_guid" size="40" value="<?php print $cover->cover_guid; ?>" /></td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_in_date">Data de Entrada:</label></th>
              <td>
                <input type="text" name="cover_in_date" id="cover_in_date" class="jquery-calendar" size="10" value="<?php print $cover_in_date; ?>" />
                <input type="text" name="cover_in_hour" id="cover_in_hour" size="8" value="<?php print $cover_in_hour; ?>" />
              </td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_out_date<?php print $a; ?>">Data de Sa&iacute;da:</label></th>
              <td>
                <input type="text" name="cover_out_date<?php print $a; ?>" id="cover_out_date<?php print $a; ?>" class="jquery-calendar" size="10" value="<?php print $cover_out_date; ?>" />
                <input type="text" name="cover_out_hour<?php print $a; ?>" id="cover_out_hour<?php print $a; ?>" size="8" value="<?php print $cover_out_hour; ?>" />
              </td>
            </tr>
            <tr>
              <th style="text-align: right;"><label for="cover_order">Ordem:</label></th>
              <td>
                <select name="cover_order" id="cover_order">
                  <?php for($a = 1; $a < 10; $a++) : ?><option <?php print ($a == $cover->cover_order) ? 'selected="selected"' : ''; ?>><?php print $a; ?></option><?php endfor; ?>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
        <?php if(!empty($thumbs)) : ?>
        <div style="float: right; padding: 0px 10px 10px 10px; background: #EEEEEE;">
          <h4>Imagem:</h4>
          <input type="radio" name="image_id" id="image_id" value="0" <?php print (empty($cover->image_id)) ? 'checked="checked"' : ''; ?> />Sem Imagem<br />
          <?php foreach($thumbs as $thumb) : ?>
          <input type="radio" name="image_id" id="image_id" value="<?php print $thumb; ?>" <?php print ($thumb == $cover->image_id) ? 'checked="checked"' : ''; ?> /><?php $image = image_downsize($thumb, 'thumbnail'); print "<img src='{$image[0]}' alt='' width='75px' style='margin: 5px;' />"; ?><br />
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <br clear="all" />
      </div>
      <br clear="all" />
    </div>
    <?php
  }
  
  /****************************************************************************
    covers_save
  ****************************************************************************/
  function covers_save($post_id)
  {
    if(!current_user_can('Manage Covers'))
      return false;
    
    global $wpdb;
    
    if(!empty($_POST['cover_area_id']))
    {
      $cover_area_id = $_POST['cover_area_id'];
      
      $cover_title = $_POST['cover_title'];
      $cover_excerpt = $_POST['cover_excerpt'];
      $cover_guid = $_POST['cover_guid'];
      $cover_order = $_POST['cover_order'];
      $cover_in_date = $_POST['cover_in_date'].' '.$_POST['cover_in_hour'];
      $cover_out_date = $_POST['cover_out_date'].' '.$_POST['cover_out_hour'];
      
      $cover_in_date = (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', $cover_in_date)) ? preg_replace('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3-$2-$1 $4', $cover_in_date) : $today;
      $cover_out_date = (preg_match('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', $cover_out_date)) ? preg_replace('/([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$3-$2-$1 $4', $cover_out_date) : $next_week;
      
      $image_id = (!empty($_POST['image_id'])) ? $_POST['image_id'] : 0;
      
      if($wpdb->get_var("SELECT post_id FROM {$wpdb->covers} WHERE post_id = {$post_id}"))
      {
        $wpdb->query("UPDATE {$wpdb->covers} SET cover_area_id = {$cover_area_id}, cover_title = '{$cover_title}', cover_excerpt = '{$cover_excerpt}', cover_guid = '{$cover_guid}', cover_order = '{$cover_order}', cover_in_date = '{$cover_in_date}', cover_out_date = '{$cover_out_date}', image_id = '{$image_id}' WHERE post_id = {$post_id}");
      }
      else
      {
        $wpdb->query
        ("
          INSERT INTO {$wpdb->covers} (post_id, cover_title, cover_excerpt, cover_guid, cover_order, cover_in_date, cover_out_date, cover_area_id, image_id) 
          VALUES ('{$post_id}', '{$cover_title}', '{$cover_excerpt}', '{$cover_guid}', '{$cover_order}', '{$cover_in_date}', '{$cover_out_date}', {$cover_area_id}, {$image_id})
        ");
      }
    }
  }
  
  /****************************************************************************
    covers_delete
  ****************************************************************************/
  function covers_delete($post_id)
  {
    global $wpdb;
    
    $wpdb->query("DELETE FROM {$wpdb->covers} WHERE post_id = {$post_id}");
  }
  
  /****************************************************************************
    have_covers
  ****************************************************************************/
  function have_posts($cover_area_id, $cover_area_max = 10, $date = null)
  {
    global $wpdb;
    
    if($cover_area_id !== $this->cover_area_id)
    {
      $this->covers = array();
      $this->cover_area_id = $cover_area_id;
      $this->cover_area_max = $cover_area_max;
      $this->cover_area_count = 0;
      
      $this->cover_area_max = $wpdb->get_var("SELECT cover_area_max FROM {$wpdb->covers_areas} WHERE cover_area_id = {$cover_area_id}");
      
      if($cover_area_max < $this->cover_area_max) $this->cover_area_max = $cover_area_max;
      if(empty($date)) $date = date('Y-m-d H:i:s');
      
      for($order = 1; $order <= $this->cover_area_max; $order++)
      {
        $cover = $wpdb->get_row("SELECT cover_title, cover_excerpt, cover_guid, cover_order, post_id, image_id FROM {$wpdb->covers} WHERE cover_order = {$order} AND cover_in_date <= '{$date}' AND cover_out_date >= '{$date}' AND cover_area_id = {$cover_area_id} ORDER BY cover_in_date DESC");
        
        if(!empty($cover))
          array_push($this->covers, $cover);
      }
      
      $this->cover_area_max = count($this->covers);
      
      return (!empty($this->covers)) ? true : false;
    }
    else
    {
      return ($this->cover_area_count < $this->cover_area_max) ? true : false;
    }
  }
  
  /****************************************************************************
    the_cover
  ****************************************************************************/
  function the_post()
  {
    $this->cover_area_count++;
    
    $leads = new WP_Query('p='.$this->the_id());
    ($leads->have_posts()) ? $leads->the_post() : $this->the_cover();
  }
  
  /****************************************************************************
    the_id
  ****************************************************************************/
  function the_id()
  {
    return $this->covers[$this->cover_area_count - 1]->post_id;
  }
  
  /****************************************************************************
    the_title
  ****************************************************************************/
  function the_title()
  {
    print ($this->covers[$this->cover_area_count - 1]->cover_title) ? $this->covers[$this->cover_area_count - 1]->cover_title : the_title();
  }
  
  /****************************************************************************
    the_excerpt
  ****************************************************************************/
  function the_excerpt()
  {
    print ($this->covers[$this->cover_area_count - 1]->cover_excerpt) ? $this->covers[$this->cover_area_count - 1]->cover_excerpt : the_excerpt();
  }
  
  /****************************************************************************
    the_permalink
  ****************************************************************************/
  function the_permalink()
  {
    print ($this->covers[$this->cover_area_count - 1]->cover_guid) ? $this->covers[$this->cover_area_count - 1]->cover_guid : the_permalink();
  }
  
  /****************************************************************************
    the_thumb
  ****************************************************************************/
  function the_thumb($size = 'medium', $add = '')
  {
    if(!empty($this->covers[$this->cover_area_count - 1]->image_id))
    {
      $image = image_downsize($this->covers[$this->cover_area_count - 1]->image_id, $size);
      
      print "<img src='{$image[0]}' alt='' {$add} />";
    }
  }
  
  // CONSTRUTOR ///////////////////////////////////////////////////////////////
  /****************************************************************************
    Covers_Control
  ****************************************************************************/
  function covers()
  {
    $this->set_tables();
    
    // editar
    add_action('edit_form_advanced', array(&$this, 'covers_edit'));
    
    // salvar
    add_action('publish_post', array(&$this, 'covers_save'));
    
    // deletar
    add_action('delete_post', array(&$this, 'covers_delete'));
    
    // adicionando o menu
    add_action('admin_menu', array(&$this, 'covers_menu'));
    
    // instalando o plugin
    register_activation_hook(__FILE__, array(&$this, 'covers_install'));
    
    // desinstalando o plugin
    register_deactivation_hook(__FILE__, array(&$this, 'covers_uninstall'));
  }
  
  // DESTRUTOR ////////////////////////////////////////////////////////////////
  
}

$covers = new covers();

?>
