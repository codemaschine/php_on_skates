<?php
defined('SKATES') or die();  // only execute if included by skates script


class FileGenerator {

  protected $filename;
  protected $fileUri;
  protected $templateUri;
  protected $markers = array();
  

  /**
   * @param string $templateUri The templateUri file from which the file should generated
   * @param string $file The file location and name where the generated fild should be stored. Starting directory is project root.
   */
  public function __construct($templateUri = null, $filename = null) {
  	$this->templateUri = $templateUri;
    $this->setFilename($filename);
  }

  public function setFilename($filename) {
  	$this->filename = $filename;
    $this->fileUri = ROOT_DIR.$filename;
  }
  public function setTemplateUri($templateUri) {
    $this->templateUri = $templateUri;
  }

  public function setMarker($name, $value) {
    $this->markers[$name] = $value;
  }

  public function setMarkers($markers) {
    $this->markers = array_merge($this->markers, $markers);
  }

  public function generate($markers = null){
    if ($markers)
      $this->markers = array_merge($this->markers, $markers);

    if (file_exists($this->fileUri)) {
      echo "File $this->filename exists. Skipping.\n";
      return false;
    }

    $template = file_get_contents(dirname(__FILE__).'/'.$this->templateUri);
    foreach ($this->markers as $name => $value) {
      $template = str_replace("___{$name}___", $value, $template);
    }
    file_put_contents($this->fileUri, $template);

    echo "created file $this->filename\n";
    return true;
  }
}


function model_generator($model_name, $fields){
	if (!$fields)
		die("ERROR: Missing field defs");

  $g = new FileGenerator('model_template.php', 'app/model/'.strtolowerunderscore($model_name).'.php');

  if ($fields['id'])
    unset($fields['id']);
  
  $field_defs_str = '';
  foreach ($fields as $field => $type) {
    $field_defs_str .= "'$field' => '$type',
      ";
  }

  $mass_assignable_str = "'".join("', '", array_diff(array_keys($fields), array('created_at', 'updated_at')))."'";
	$table_name = strtolowerunderscore(Inflect::pluralize($model_name));

  return $g->generate(array(
    'model_name' => strtouppercamelcase($model_name),
    'table_name' => $table_name,
    'mass_assignables' => $mass_assignable_str,
    'field_definitions' => $field_defs_str));
}

function migration_generator($migration_name, $fields){
  $indexes = array();

  $field_defs_str = '';
  foreach ($fields as $field => $type) {
    $field_defs_str .= "'$field' => '$type',
            ";

    if (mb_substr($field, -3) === '_id')
      $indexes []= $field;
  }

  $migration_name = strtolowerunderscore($migration_name);  // we only need the underscore-notations. No problem if it is already underscore

  $date_str = date('Y_m_d_H_i_s');
  $filename = $date_str.'_'.$migration_name.'.php';
  $matches = null;

  $g = new FileGenerator('migration_template.php', 'app/migrations/'.$filename);
  if ($fields && preg_match('/^(add|change|remove)_(column_)?(\w+)_(to|in|from)_(\w+)$/',$migration_name, $matches)) {
    if ($matches[1] === 'add')
      $g->setTemplateUri('migration_add_template.php');
    elseif ($matches[1] === 'change')
      $g->setTemplateUri('migration_change_template.php');
    else
      $g->setTemplateUri('migration_remove_template.php');

    foreach ($fields as $key => $value) {
      $field_name = $key;
      $type = $value;
      break; // only use the first definition of field_name:type
    }
    $table_name = $matches[5];
    $index_def = mb_substr($field_name, -3) === '_id' ? "
        \$this->add_index('$table_name', '$field_name');" :
      '';

    $g->setMarkers(array(
      'table_name' => $table_name,
      'field_name' => $field_name,
      'type' => $type,
      'index_definition' => $index_def
      ));

  }
  elseif ($fields && preg_match('/^(create)_(table_)?(\w+)$/',$migration_name, $matches)) {
    $table_name = $matches[3];
    $g->setTemplateUri('migration_create_template.php');


    $indexes_defs_str = '';
    foreach ($indexes as $field_name) {
      $indexes_defs_str .= "
        \$this->add_index('$table_name', '$field_name');";
    }

    $g->setMarkers(array(
      'table_name' => $table_name,
      'field_definitions' => $field_defs_str,
      'index_definitions' => $indexes_defs_str
      ));
  }

  $g->generate(array('date' => $date_str));
}

function controller_generator($model_name) {
  $model_name = strtouppercamelcase($model_name);  // just to make sure that is upper camel case
  $filename = strtolowerunderscore($model_name).'.php';
  $g = new FileGenerator('controller_template.php', 'app/controller/'.$filename);
  
  $model_var_name = strtolowerunderscore($model_name);
  $g->generate(array(
  		'model_name' => $model_name,
  		'model_var_name' => $model_var_name,
  		'model_var_name_pl' => Inflect::pluralize($model_var_name),
  ));
}


function views_generator($model_name, $fields){
	if (!$fields)
		die("ERROR: Missing field defs");
	
    $model_var_name = strtolowerunderscore($model_name);
    $model_readable_name = ucfirst(str_replace('_', ' ', $model_var_name));
	$views_dir = dirname(__FILE__).'/../../app/views/'.$model_var_name;
    if (!file_exists($views_dir))
		mkdir($views_dir);
	
	$views_dir = 'app/views/'.$model_var_name.'/';
	$model_var_name_pl = Inflect::pluralize($model_var_name);
	
	
	// index view
	$g = new FileGenerator('view_index_template.php', $views_dir.'index.php');
	$listing_header_rows = '';
	foreach ($fields as $name => $type) {
		$listing_header_rows .= "\n      <th>".ucfirst(str_replace('_', ' ',$name))."</th>";
	}
	$listing_body_rows = '';
	foreach ($fields as $name => $type) {
		$listing_body_rows .= "\n      <td><?= \${$model_var_name}->get('$name'); ?></td>";
	}
	$g->generate(array(
			'model_var_name' => $model_var_name,
			'model_readable_name' => $model_readable_name,
			'model_readable_name_pl' => Inflect::pluralize($model_readable_name),
			'model_var_name_pl' => $model_var_name_pl,
			'listing_header_rows' => $listing_header_rows,
			'listing_body_rows' => $listing_body_rows
	));
	
	
	// show view
	$g = new FileGenerator('view_show_template.php', $views_dir.'show.php');
	$show_entries = '';
	foreach ($fields as $name => $type) {
		$show_entries .= '
<p>
	<strong>'.ucfirst(str_replace('_', ' ',$name)).'</strong>
	<?= $'.$model_var_name.'->get(\''.$name.'\'); ?>
</p>
';
	}
	$g->generate(array(
			'model_var_name' => $model_var_name,
			'model_readable_name' => $model_readable_name,
			'show_entries' => $show_entries
	));
	
	
	// is multipart form?
	$multipart = false;
	foreach ($fields as $name => $type) {
		if ($type === 'attachment')
			$multipart = true;
	}
	
	
	// new view
	$g = new FileGenerator('view_new_template.php', $views_dir.'new.php');
	$g->generate(array(
			'model_var_name' => $model_var_name,
			'model_readable_name' => $model_readable_name,
			'multipart' => ($multipart ? ", array('multipart' => true)" : '')
	));
	
	// edit view
	$g = new FileGenerator('view_edit_template.php', $views_dir.'edit.php');
	$g->generate(array(
			'model_var_name' => $model_var_name,
			'model_readable_name' => $model_readable_name,
			'multipart' => ($multipart ? ", array('multipart' => true)" : '')
	));
	
	// form partial
	$g = new FileGenerator('view_form_template.php', $views_dir.'_form.php');
	$form_fields = '';
	foreach ($fields as $name => $type) {
		$form_fields .= '
<div class="field">
	<?= $f->label(\''.$name.'\'); ?><br>
	<?= $f->';
switch ($type) {
	case 'bool':
	case 'boolean':
		$form_fields .= 'check_box'; break;
	case 'text':
		$form_fields .= 'text_area'; break;
	case 'attachment':
		$form_fields .= 'file_field'; break;
	default:
		$form_fields .= 'text_field';
}
$form_fields .= '(\''.$name.'\'); ?>
</div>
';
	}
	$g->generate(array(
			'model_var_name' => $model_var_name,
			'model_readable_name' => $model_readable_name,
			'form_fields' => $form_fields
	));
}