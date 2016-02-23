<?php

class Migration_###date### extends MpmMigration
{

    public function up(PDO &$pdo)
    {
        $this->create_table('###table_name###', array(
            ###field_definitions###
        ));###index_definitions###
    }

    public function down(PDO &$pdo)
    {
        $this->drop_table('###table_name###');
    }

}

?>
