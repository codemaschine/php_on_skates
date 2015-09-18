<?php foreach ($posts as $p) { ?>

  <h4><?= $p->get('name'); ?></h4>

  <p>Kategorie: <?= $p->get('category'); ?></p>
  <p><?= $p->get('message'); ?></p>
  
  
  <?php } ?>