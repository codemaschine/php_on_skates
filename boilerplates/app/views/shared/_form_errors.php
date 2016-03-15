<div id="error_explanation">
	<h2>Please check your inputs!</h2>
	
	<ul>
	<?php foreach ($errors as $field => $value): ?>
	  <li class="error_message"><?= is_int($field) ? $value : '<span class="property">'.ucfirst(str_replace('_', ' ', $field))."</span> $value"; ?></li>
	<?php endforeach; ?>
	</ul>
</div>