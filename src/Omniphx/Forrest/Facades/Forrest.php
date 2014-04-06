<?php namespace Omniphx\Forrest\Facades;
 
use Illuminate\Support\Facades\Facade;
 
class Forrest extends Facade {
 
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { return 'forrest'; }
 
}