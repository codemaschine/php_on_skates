<h1>Edit ___model_readable_name___</h1>

<?php $f = Form::open('___model_var_name___', $___model_var_name___, array('action' => 'update', 'id' => $___model_var_name___->get_id())___multipart___); ?>
  <?php render_partial('form', array('f' => $f, '___model_var_name___' => $___model_var_name___)); ?>
  
  <?= $f->submit('Update'); ?>
<?php $f->close(); ?>

<?= link_to('Back', 'index'); ?>