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
      call_user_func(array($this,sprintf('%sConnect', $request->getParameter('provider'))), $request);
    else
      $this->redirect('@homepage');

    return sfView::NONE;
  }

  /**
   * Executes callback action
   *
   * @param sfWebRequest $request
   */
  public function executeCallback(sfWebRequest $request)
  {
    $this->forward404Unless($request->getParameter('provider'));

    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));

    if( ! $this->getUser()->isAuthenticated())
      call_user_func(array($this,sprintf('%sCallback', $request->getParameter('provider'))), $request);
    else
      $this->redirect('@homepage');

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
      $token->setContent($this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))));
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
          $accessToken = $this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
          $token->setContent($accessToken);
          if($accessToken['expires_at']) $token->setExpiresAt($accessToken['expires_at']);
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

  /**
   * Default connect method, usually called by __call
   *
   * @param $request
   */
  public function defaultConnect($request)
  {
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));

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

  /**
   * Facebook connect
   *
   * @param sfWebRequest $request
   */
  protected function facebookConnect(sfWebRequest $request)
  {
    $this->getUser()->setAttribute('state', md5(uniqid(rand(), true)) , sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
    
    $config = sfConfig::get('app_cacophony');
    
    $this->getContext()->getConfiguration()->loadHelpers('Url');
    
    $this->redirect(
      sprintf(
        '%s?%s',
        $config['providers'][$request->getParameter('provider')]['authorize_url'],
        http_build_query(
          array(
            'client_id'     => $config['providers'][$request->getParameter('provider')]['consumer_key'],
            'redirect_uri'  => $this->getContext()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for(sprintf('@sf_cacophony_callback?provider=%s',$request->getParameter('provider')),true) : 'oob',
            'state'         => $this->getUser()->getAttribute('state', null , sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))),
            'scope'         => $config['providers'][$request->getParameter('provider')]['scope'] ?: null
          )
        )
      )
    );
  }

  /**
   * Netflix connect
   *
   * @param $request
   */
  protected function netflixConnect($request)
  {
    $config = sfConfig::get('app_cacophony');
    $result = sfCacophonyOAuth::getRequestToken($request->getParameter('provider'));

    $this->getUser()->setAttribute('requestToken',$result,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));

    $this->redirect(
      sprintf(
        '%s&%s',
        $result['login_url'], // access token is already added to login_url
        http_build_query(
          array(
            'oauth_consumer_key' => $config['providers'][$request->getParameter('provider')]['consumer_key'],
            'application_name' => $result['application_name'],
            'oauth_callback'  => $this->getContext()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for(sprintf('@sf_cacophony_callback?provider=%s',$request->getParameter('provider')),true) : 'oob'
          )
        )
      )
    );
  }

  /**
   * Processes the callback from OAuth provider, usually called by __call
   *
   * @param sfRequest $request
   * @return sfView::NONE
   */
  protected function defaultCallback(sfRequest $request)
  {
    $config = sfConfig::get('app_cacophony');
    $this->forward404Unless(in_array($request->getParameter('provider'), array_keys($config['providers'])));

    if( ! $this->getUser()->isAuthenticated())
    {
      $requestToken = $this->getUser()->getAttribute('requestToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));

      if($requestToken)
      {
        try
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

          // add me to session
          $me = sfCacophonyOAuth::getMe(
            $request->getParameter('provider'),
            $this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')))
          );

          $this->getUser()->setAttribute('me',$me['normalized'],sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
        }
        catch(Exception $e)
        {
          $this->getUser()->setFlash('error', sprintf('Failed to retrieve access token: %s',$e->getMessage()));
          $this->redirect('@homepage');
        }
      }
    }
    else $this->redirect('@homepage');
  }

  /**
   * Facebook callback (OAuth 2)
   *
   * @param $request
   * @return string
   * @throws Exception
   */
  public function facebookCallback($request)
  {
    // CSFR protection as adviced on the
    // http://developers.facebook.com/docs/authentication/
    if($request->getParameter('state') != $this->getUser()->getAttribute('state', null , sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider'))))
      throw new Exception('CSRF attack detected');
    
    if( ! $this->getUser()->isAuthenticated())
    {
      try
      {
        $this->getUser()->setAttribute(
          'accessToken',
          sfCacophonyOAuth::getFacebookToken($request->getParameter('code')),
          'sfCacophonyPlugin/facebook'
        );

        // add me to session
        $me = sfCacophonyOAuth::getMe(
          $request->getParameter('provider'),
          $this->getUser()->getAttribute('accessToken',null,sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')))
        );

        $this->getUser()->setAttribute('me',$me['normalized'],sprintf('sfCacophonyPlugin/%s',$request->getParameter('provider')));
      }
      catch(Exception $e)
      {
        $this->getUser()->setFlash('error', sprintf('Failed to retrieve access token: %s',$e->getMessage()));
        $this->redirect('@homepage');
      }
    }
    else $this->redirect('@homepage');
  }

  /**
   * Intercepts all undefined Connect and Callback methods and redirects to default ones.
   *
   * @param $method
   * @param $params
   */
  public function __call($method,$params)
  {
    if(stripos($method,'connect') !== false) $this->defaultConnect($params[0]);
    if(stripos($method,'callback') !== false) $this->defaultCallback($params[0]);
  }
}