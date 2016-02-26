<h1>New ___model_readable_name___</h1>

<?php $f = Form::open('___model_var_name___', $___model_var_name___, 'create'); ?>
  <?php render_partial('form', array('f' => $f, '___model_var_name___' => $___model_var_name___)); ?>
  
  <?= $f->submit('Create'); ?>
<?php $f->close(); ?>

<?= link_to('Back', 'index'); ?>