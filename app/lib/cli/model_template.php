<?php


class ###model_name### extends AbstractModel {

  protected static $table_name = '###table_name###';

  protected function attribute_definitions() {
    return array (
      // Define attributes with default values here (do not define id-column!!!)
      ###field_definitions###
    );
  }

  protected function configuration() {
    $this->mass_assignable = array(###mass_assignables###);

    // define associations and validations here
    // ...
  }


}
