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
  
  public function executeConnect(sfRequest $request)
  {
    $this->forward404Unless($request->getParameter('provider'));
    
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));
    
    if( ! $this->getUser()->isAuthenticated())
    {
      $result = sfCacophonyOAuth::getRequestToken($request->getParameter('provider'));
      
      $this->getUser()->setAttribute('requestToken',$result,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
      
      $this->redirect(
        sprintf(
          '%s?%s',
          $config['providers'][$request->getParameter('provider')]['authorize_url'],
          http_build_query(array('oauth_token' => $result['oauth_token']))
        )
      );
    }
    else $this->redirect('@homepage');
  }
  
  /**
   * Processes th callback from OAuth provider
   * 
   * @param sfRequest $request 
   */
  public function executeCallback(sfRequest $request)
  {
    $this->forward404Unless($request->getParameter('provider'));
    
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));
    
    if( ! $this->getUser()->isAuthenticated())
    {
      $requestToken = $this->getUser()->getAttribute('requestToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
      
      if($requestToken)
      {
        $this->getUser()->setAttribute(
          'accessToken',
          sfCacophonyOAuth::getAccessToken(
            $request->getParameter('provider'),
            $request->getParameter('oauth_token'),
            $requestToken['oauth_token_secret'],
            $request->getParameter('oauth_verifier')
          ),
          sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))
        );

        $this->getUser()->getAttributeHolder()->remove('requestToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
      }
    }
    else $this->redirect('@homepage');
    
    return sfView::NONE;
  }
  
  /**
   * This is only example of what you can do.
   * 
   * You should write your own method to handle the business logic required
   * for your app and specify it in the sfCacophonyFilter in the filters.yml
   *
   * @param sfRequest $request 
   */
  public function executeRegister(sfRequest $request)
  {
    $result = sfCacophonyOAuth::getMe(
      $request->getParameter('provider'),
      $this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')))
    );
    
    $token = new Token();
    $token->fromArray($result['normalized']);
    $token->fromArray($this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))));
    $token->setProvider($request->getParameter('provider'));
    
    $sf_guard_user = new sfGuardUser();
    $sf_guard_user->fromArray($result['normalized']);
    $sf_guard_user['Token']->add($token);
    $sf_guard_user->save();
    
    $this->getUser()->signin($sf_guard_user);
    
    $this->redirect('@homepage');
  }
}