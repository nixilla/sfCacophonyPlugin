<?php

/**
 * sfCacophonyConsumer actions.
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyConsumer
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class BasesfCacophonyConsumerActions extends sfActions
{
  /**
   * Connects user to given provider
   * @param sfRequest $request 
   */
  
  public function executeConnect($request)
  {
    $this->forward404Unless($request->getParameter('provider'));
    
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));
    
    $result = $this->getUser()->connect($request->getParameter('provider'));
    
    if($this->getUser()->getAttribute('oauth_secret'))
    {
      $this->redirect(
        sprintf(
          '%s?%s',
          $config['providers'][$request->getParameter('provider')]['authorize_url'],
          http_build_query(array('oauth_token' => $result['oauth_token']))
        )
      );
    }
    else $this->redirect( $config['providers'][$request->getParameter('provider')]['redirect_to'] ?: '@homepage' );
  }
  
  
  public function executeCallback($request)
  {
    
  }
}
