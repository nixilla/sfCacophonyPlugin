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
    
    /**
     * You might want to get user info from the provider like this
     */
    $result = sfCacophonyOAuth::getMe(
      $request->getParameter('provider'),
      $this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')))
    );
    
    /**
     * You might want to check if user exists like this:
     */
    $sf_guard_user = Doctrine_Core::getTable('sfGuardUser')->createQuery('u')
      ->innerJoin('u.Tokens t')
      ->where('t.providers_user_id = ?',$result['normalized']['providers_user_id'])
      ->fetchOne();
    
    if( ! $sf_guard_user)
    {
      /**
       * If user doesn't exist, you might want to add him/her, like this:
       */
      $token = new Token();
      $token->fromArray($result['normalized']);
      $token->fromArray($this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))));
      $token->setProvider($request->getParameter('provider'));

      $sf_guard_user = new sfGuardUser();
      $sf_guard_user->fromArray($result['normalized']);
      $sf_guard_user['Tokens']->add($token);
      $sf_guard_user->save();
    }
    else
    {
      /**
       * Or if the user exists, update it's token keys
       */
      foreach($sf_guard_user['Tokens'] as $token)
      {
        if($token['provider'] == $request->getParameter('provider'))
        {
          $token->fromArray($this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))));
          $token->save();
          break;
        }
      }
    }
    
    /**
     * At the end, you might want to log in user like this:
     */
    $this->getUser()->signin($sf_guard_user);
    
    /**
     * and redirect to homepage, or wherever you want
     */
    $this->redirect('@homepage');
  }
}