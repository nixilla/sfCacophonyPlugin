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

      if (sfConfig::get('sf_environment') == 'test')
        $enabledModules[] = 'sfCacophonyMock';

      sfConfig::set('sf_enabled_modules', $enabledModules);

      foreach(array('sfCacophonyConsumer','sfCacophonyProvider','sfCacophonyMock') as $module)
      {
        if (in_array($module, $enabledModules))
        {
          $this->dispatcher->connect('routing.load_configuration', array('sfCacophonyRouting', 'addRoutesFor'.str_replace('sfCacophony', '', $module)));
        }
      }
    }
  }
}
