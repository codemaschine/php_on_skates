<?php

class Migration____date___ extends MpmMigration {
  public function up(PDO &$pdo) {
    $this->change_column('___table_name___', '___field_name___', '___type___');
  }

  public function down(PDO &$pdo) {
  }
}
