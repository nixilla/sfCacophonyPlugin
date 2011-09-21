<?php

/**
 * sfCacophonyPlugin configuration.
 * 
 * @package    sfCacophonyPlugin
 * @subpackage config
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    $config = sfConfig::get('app_cacophony');
    if($config['plugin']['routes_register'])
    {
      $enabledModules = sfConfig::get('sf_enabled_modules', array());

      foreach(array('sfCacophonyConsumer','sfCacophonyProvider') as $module)
      {
        if (in_array($module, $enabledModules))
        {
          $this->dispatcher->connect('routing.load_configuration', array('sfCacophonyRouting', 'addRouteFor'.str_replace('sfCacophony', '', $module)));
        }
      }
    }
  }
}
