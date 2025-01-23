<?php
namespace PHPSTORM_META {
  override(\AbstractModel::get(), map([
    'created_at' => \SKATES\DateTime::class,
    'updated_at' => \SKATES\DateTime::class,
  ]));
}
