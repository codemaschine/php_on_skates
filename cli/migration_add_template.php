<?php

class Migration____date___ extends MpmMigration
{

    public function up(PDO &$pdo)
    {
        $this->add_column('___table_name___', '___field_name___', '___type___');___index_definition___
    }

    public function down(PDO &$pdo)
    {
        $this->remove_column('___table_name___', '___field_name___');
    }

}

?>
