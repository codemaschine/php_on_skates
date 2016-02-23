<?php

class Migration_###date### extends MpmMigration
{

    public function up(PDO &$pdo)
    {
        $this->change_column('###table_name###', '###field_name###', '###type###');
    }

    public function down(PDO &$pdo)
    {
    }

}

?>
