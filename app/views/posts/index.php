


<div id="alle_posts" class="alle_posts">
  <?php render_partial('posts/_posts'); ?>
</div>


<h3>Neuen Post erstellen</h3>

<?php $f = RemoteForm::open('post', $post, 'posts.php?action=create', array('update' => 'alle_posts')); ?>

  <?php if ($post->get_errors()) {
  				echo $post->get_errors_as_message(); 
				}
  ?>
  
  <div class="field">
    <?= $f->label('name', 'Name'); ?><br>
	  <?= $f->text_field('name'); ?>
	</div>
	
	
	<div class="field">
    <?= $f->label('category', 'Kategorie'); ?><br>
	  <?= $f->text_field('category', array('class' => 'blablub')); ?>
	</div>
	
	<div class="field">
	  <?= $f->label('message', 'Nachricht'); ?><br>
	  <?= $f->text_area('message'); ?>
	</div>
	
	<div class="field">
	  <?= $f->submit('Abschicken'); ?>
	</div>

<?php $f->close(); ?>