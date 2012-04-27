<?php

/**
 * sfCacophonyPlugin routing.
 * 
 * @package    sfCacophonyPlugin
 * @subpackage routing
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   * @static
   */
    
  static public function addRoutesForConsumer(sfEvent $event)
  {
    $r = $event->getSubject();
    $r->prependRoute('sf_cacophony_connect', new sfRoute('/oauth/connect/:provider', array('module' => 'sfCacophonyConsumer', 'action' => 'connect')));
    $r->prependRoute('sf_cacophony_callback', new sfRoute('/oauth/callback/:provider', array('module' => 'sfCacophonyConsumer', 'action' => 'callback')));
    $r->prependRoute('sf_cacophony_register', new sfRoute('/oauth/register/:provider', array('module' => 'sfCacophonyConsumer', 'action' => 'register')));
  }

  /**
   * Listens to the routing.load_configuration event.
   *
   * Adds route for mock module which mocks OAuth providers behaviour
   *
   * @static
   * @param sfEvent $event
   */
  static public function addRoutesForMock(sfEvent $event)
  {
    $r = $event->getSubject();
    $r->prependRoute('sf_cacophony_mock', new sfRoute('/oauth/mock/:provider/:method', array('module' => 'sfCacophonyMock', 'action' => 'index')));
  }
  /**
   * Listens to the routing.load_configuration event.
   * 
   * @param sfEvent $event 
   * @static
   * @todo
   * 
   */
  static public function addRoutesForProvider(sfEvent $event)
  {
    $r = $event->getSubject();
  }
}
