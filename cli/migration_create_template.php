<?php

class Migration____date___ extends MpmMigration {
  public function up(PDO &$pdo) {
    $this->create_table('___table_name___', [
      ___field_definitions___
    ]);___index_definitions___
  }

  public function down(PDO &$pdo) {
    $this->drop_table('___table_name___');
  }
}
