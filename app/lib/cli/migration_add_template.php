<?php

class Migration_###date### extends MpmMigration
{

    public function up(PDO &$pdo)
    {
        $this->add_column('###table_name###', '###field_name###', '###type###');###index_definition###
    }

    public function down(PDO &$pdo)
    {
        $this->remove_column('###table_name###', '###field_name###');
    }

}

?>
