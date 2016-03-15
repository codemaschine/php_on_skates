<?php


class ___model_name___ extends AbstractModel {

  protected static $table_name = '___table_name___';

  public static function attribute_definitions() {
    return array (
      // Define attributes with default values here (do not define id-column!!!)
      ___field_definitions___
    );
  }

  protected function configuration() {
    $this->mass_assignable = array(___mass_assignables___);

    // define associations and validations here
    // ...
  }


}
