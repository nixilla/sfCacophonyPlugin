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
    
    if($this->getUser()->isAuthenticated()) // && $this->hasTokenFor($provider))
    {
      sfCacophonyOAuth::refreshToken($this->getUser()->getGuardUser(),$provider);
      $this->redirect( $config['providers'][$request->getParameter('provider')]['redirect_to'] ?: '@homepage' );
    }
    else
    {
      $result = sfCacophonyOAuth::getRequestToken($request->getParameter('provider'));
      // @todo need to check if($result)
      $this->getUser()->setAttribute('oauth_token_secret',$result['oauth_token_secret']);
      $this->redirect(
        sprintf(
          '%s?%s',
          $config['providers'][$request->getParameter('provider')]['authorize_url'],
          http_build_query(array('oauth_token' => $result['oauth_token']))
        )
      );
    }
  }
  
  /**
   * Processes th callback from OAuth provider
   * 
   * @param sfRequest $request 
   */
  public function executeCallback($request)
  {
    $this->forward404Unless($request->getParameter('provider'));
    
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));
    
    if( ! $this->getUser()->isAuthenticated())
    {
      $result = sfCacophonyOAuth::getAccessToken(
        $request->getParameter('provider'),
        $request->getParameter('oauth_token'),
        $this->getUser()->getAttribute('oauth_token_secret'),
        $request->getParameter('oauth_verifier')
      );
      $this->getUser()->setAttribute('accessToken', $result);
    }
    
    // @todo This needs to be configurable
    $this->forward('sfGuardRegister', 'index');
  }
}