<?php
class covers_areas
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
  
  /****************************************************************************    covers_areas_control
  ****************************************************************************/
  function covers_areas_control()
  {
    $id = $_REQUEST['id'];
    
    switch($_REQUEST['action'])
    {
      case 'add_area':
        $cover_area_max = $_REQUEST['cover_area_max'];
        $cover_area_name = $_REQUEST['cover_area_name'];
        
        $this->covers_areas_add($cover_area_max, $cover_area_name);
      break;
      case 'edit_area':
        $cover_area_id = $_REQUEST['cover_area_id'];
        $cover_area_max = $_REQUEST['cover_area_max'];
        $cover_area_name = $_REQUEST['cover_area_name'];
        
        $this->covers_areas_edit($cover_area_id, $cover_area_max, $cover_area_name);
      break;
      case 'delete_area':
        $cover_area_id = $_REQUEST['cover_area_id'];
        
        $this->covers_areas_delete($cover_area_id);
      break;
    }
    
    $this->get_error();
    $this->get_sucess();
    
    ?>
    <div class="wrap">
      <?php $this->covers_areas_table_list(); ?>
    </div>
    
    <div class="wrap">
      <?php $this->covers_areas_form($id); ?>
    </div>
    <?php
  }
  
  /****************************************************************************
    Listar
  ****************************************************************************/
  function covers_areas_table_list()
  {
    global $wpdb;
    
    $areas = $wpdb->get_results("SELECT cover_area_id, cover_area_max, cover_area_name FROM {$wpdb->covers_areas}");
    
    ?>
    <h2><?php print __('Gerenciar Áreas'); ?></h2>
    
    <form action="" method="post">
      <div class="tablenav">
        <div class="alignleft">
          <button type="submit" name="action" value="delete_area" class="button-secondary delete"><?php print __('delete'); ?></button>
        </div>
        <br class="clear">
      </div>
      
      <br class="clear">
      
      <table class="widefat">
        <thead>
          <tr>
            <th class="check-column"><input onclick="checkAll(document.getElementById('fc_id[]'));" type="checkbox"></th>
            <th width="80%"><?php print __('Área'); ?></th>
            <th style="text-align: center;"><?php print __('Quantidade'); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($areas as $area) : ?>
          <?php $alternate = !$alternate; ?>
          <tr <?php if($alternate) print 'class="alternate"'; ?>>
            <td class="check-column"><input type="checkbox" name="cover_area_id[]" value="<?php print $area->cover_area_id; ?>" /></td>
            <td><a href="<?php bloginfo('url'); ?>/wp-admin/admin.php?page=covers-areas&id=<?php print $area->cover_area_id; ?>"><?php print $area->cover_area_name; ?></a></td>
            <td style="text-align: center;"><?php print $area->cover_area_max; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </form>
    <?php
  }
  
  /****************************************************************************
    Form
  ****************************************************************************/
  function covers_areas_form($id = null)
  {
    global $wpdb;
    
    if(!empty($id))
      $area = $wpdb->get_row("SELECT cover_area_id, cover_area_max, cover_area_name FROM {$wpdb->covers_areas} WHERE cover_area_id = {$id}");
    
    ?>
    <h2><?php print (!empty($id)) ? __('Editar Area') : __('Adicionar Area'); ?></h2>
    
    <form action="" method="post">
      <input type="hidden" name="cover_area_id" value="<?php print $area->cover_area_id; ?>" />
      <table class="form-table">
        <tbody>
          <tr class="form-field form-required">
            <th valign="top"><label for="cover_area_name"><?php print __('Nome'); ?>:</label></th>
            <td>
              <input type="text" name="cover_area_name" id="cover_area_name" value="<?php print $area->cover_area_name; ?>" size="40" taborder="1" /><br />
              Nome da &aacute;rea
            </td>
          </tr>
          <tr>
            <th><label for="area_max"><?php print __('Quantidade'); ?>:</label></th>
            <td>
              <input type="text" name="cover_area_max" id="cover_area_max" value="<?php print $area->cover_area_max; ?>" size="40" taborder="2" /><br />
              Quantidade m&aacute;xima de capas para essa &aacute;rea
            </td>
          </tr>
        </tbody>
      </table>
      
      <?php if(!empty($id)) : ?>
        <p class="submit"><button type="submit" name="action" value="edit_area" taborder="4" class="button">Editar &raquo;</button></p>
      <?php else : ?>
        <p class="submit"><button type="submit" name="action" value="add_area" taborder="4" class="button">Adicionar &raquo;</button></p>
      <?php endif; ?>
      
    </form>
    <?php
  }
  
  /****************************************************************************
    Cadastrar
  ****************************************************************************/
  function covers_areas_add($cover_area_max, $cover_area_name)
  {
    global $wpdb;
    
    if($wpdb->query("INSERT INTO {$wpdb->covers_areas} (cover_area_max, cover_area_name) VALUES ({$cover_area_max}, '{$cover_area_name}')"))
      $this->set_sucess
      ("
        &Aacute;rea adicionada com sucesso! Agora substitua, no seu tema, as ocorrencias:
        <br />have_posts() por \$covers->have_posts({$wpdb->insert_id});
        <br />the_post() por \$covers->the_post();
        <br />the_title() por \$covers->the_title();
        <br />the_excerpt() por \$covers->the_excerpt();
        <br />the_permalink() por \$covers->the_permalink().
        <br />Apenas onde as capas forem ser usadas.
      ");
    else
      $this->set_error("Falha ao adicionar &aacute;rea!");
  }
  
  /****************************************************************************
    Editar
  ****************************************************************************/
  function covers_areas_edit($cover_area_id, $cover_area_max, $cover_area_name)
  {
    global $wpdb;
    
    if($wpdb->query("UPDATE {$wpdb->covers_areas} SET cover_area_max = {$cover_area_max}, cover_area_name = '{$cover_area_name}' WHERE cover_area_id = {$cover_area_id}"))
      $this->set_sucess("&Aacute;rea editada com sucesso!");
    else
      $this->set_error("Falha ao editar &aacute;rea!");
  }
  
  /****************************************************************************
    Deletar
  ****************************************************************************/
  function covers_areas_delete($cover_area_ids)
  {
    global $wpdb;
    
    foreach($cover_area_ids as $cover_area_id) :
      if($wpdb->query("DELETE FROM {$wpdb->covers_areas} WHERE cover_area_id = {$cover_area_id}"))
        $this->set_sucess("&Aacute;rea deletado com sucesso!");
      else
        $this->set_error("Falha ao deletar &aacute;rea!");
    endforeach;
  }
  
  // CONSTRUTOR ///////////////////////////////////////////////////////////////
  
  // DESTRUTOR ////////////////////////////////////////////////////////////////
  
}

$covers_areas = new covers_areas();

?>
