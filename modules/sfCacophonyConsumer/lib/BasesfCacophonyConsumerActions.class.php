<?php

/**
 * sfCacophonyConsumer actions.
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyConsumer
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 * @author     Jo Carter <jocarter@holler.co.uk>
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
    
    $config     = sfConfig::get('app_cacophony');
    $provider   = $request->getParameter('provider');
    
    $this->forward404Unless(in_array($provider, array_keys($config['providers'])));
    
    // if OAuth 2.0
    if (is_null($config['providers'][$provider]['request_token_url']))
    {
      $this->forward($request->getParameter('module'), 'connect2');
    }
    
    if (!$this->getUser()->isAuthenticated() || $config['plugin']['allow_multiple_tokens'])
    {
      $result = sfCacophonyOAuth::getRequestToken($provider);
      
      $this->getUser()->setAttribute('requestToken', $result, sprintf('sfCacophonyPlugin/%s', $provider));
      
      $this->redirect(
        sprintf(
          '%s?%s',
          $config['providers'][$provider]['authorize_url'],
          http_build_query(array('oauth_token' => $result['oauth_token']))
        )
      );
    }
    else $this->redirect('@homepage');
  }
  
  /**
   * Processes the callback from OAuth provider
   * 
   * @param sfRequest $request 
   */
  public function executeCallback(sfRequest $request)
  {
    $this->forward404Unless($request->getParameter('provider'));
    
    $config     = sfConfig::get('app_cacophony');
    $provider   = $request->getParameter('provider');
    
    $this->forward404Unless(in_array($provider, array_keys($config['providers'])));
    
    // if OAuth 2.0
    if (is_null($config['providers'][$provider]['request_token_url']))
    {
      $this->forward($request->getParameter('module'), 'callback2');
    }
    
    if (!$this->getUser()->isAuthenticated() || $config['plugin']['allow_multiple_tokens'])
    {
      $requestToken = $this->getUser()->getAttribute('requestToken', null, sprintf('sfCacophonyPlugin/%s', $provider));
      
      if ($requestToken)
      {
        try
        {
          $this->getUser()->setAttribute(
            'accessToken',
            sfCacophonyOAuth::getAccessToken(
              $provider,
              $request->getParameter('oauth_token'),
              $requestToken['oauth_token_secret'],
              $request->getParameter('oauth_verifier')
            ),
            sprintf('sfCacophonyPlugin/%s', $provider)
          );

          $this->getUser()->getAttributeHolder()->remove('requestToken', null, sprintf('sfCacophonyPlugin/%s', $provider));

          // add me to session
          $me = sfCacophonyOAuth::getMe(
              $provider,
              $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider))
            );

          $this->getUser()->setAttribute('me', $me['normalized'], sprintf('sfCacophonyPlugin/%s', $provider));
        }
        catch (Exception $e)
        {
          $this->getUser()->setFlash('error', sprintf('Failed to retrieve access token: %s', $e->getMessage()));
          $this->redirect('@homepage');
        }
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
    $provider   = $request->getParameter('provider');
    
    // You might want to get user info from the provider like this
    $result = sfCacophonyOAuth::getMe(
      $provider,
      $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider))
    );
    
    // You might want to check if user exists like this:
    if ($this->getUser()->isAuthenticated())
    {
      $sf_guard_user = $this->getUser()->getGuardUser();
    }
    else 
    {
      $sf_guard_user = sfGuardUserTable::getInstance()->createQuery('u')
                        ->innerJoin('u.Tokens t')
                        ->where('t.providers_user_id = ? AND provider = ?', array($result['normalized']['providers_user_id'], $provider))
                        ->fetchOne();
    }
    
    if (!$sf_guard_user)
    {
      // If user doesn't exist, you might want to add them, like this:
      $token = new Token();
      $token->fromArray($result['normalized']);
      $accessToken = $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider));
      $token->setContent($accessToken);
      if (isset($accessToken['expires_at'])) $token->setExpiresAt($accessToken['expires_at']);
      $token->setProvider($provider);

      $sf_guard_user = new sfGuardUser();
      $sf_guard_user->fromArray($result['normalized']);
      $sf_guard_user['Tokens']->add($token);
      $sf_guard_user->save();
      
      $this->postCreateHook($sf_guard_user, $token);
    }
    else
    {
      $hasToken = false;
      
      // Or if the user exists, update it's tokens
      foreach ($sf_guard_user['Tokens'] as $token)
      {
        if ($token['provider'] == $provider)
        {
          $accessToken = $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider));
          $token->setContent($accessToken);
          if (isset($accessToken['expires_at'])) $token->setExpiresAt($accessToken['expires_at']);
          $token->save();
          $hasToken = true;
          break;
        }
      }
      
      // If it's a new token - add it
      if (!$hasToken)
      {
        $token = new Token();
        $token->fromArray($result['normalized']);
        $accessToken = $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider));
        $token->setContent($accessToken);
        if (isset($accessToken['expires_at'])) $token->setExpiresAt($accessToken['expires_at']);
        $token->setProvider($provider);
        $sf_guard_user['Tokens']->add($token);
        $sf_guard_user->save();
        
        $this->postUpdateHook($sf_guard_user, $token);
      }
    }
    
    // At the end, you might want to log in user like this:
    $this->getUser()->signin($sf_guard_user);
    
    // and redirect to homepage, or wherever you want
    $this->redirect('@homepage');
  }
  
  
  /**
   * OAuth 2.0 authorize
   * 
   * @param sfWebRequest $request
   */
  public function executeConnect2(sfWebRequest $request)
  {
    $config     = sfConfig::get('app_cacophony');
    $provider   = $request->getParameter('provider');
    
    $this->getUser()->setAttribute('state', md5(uniqid(rand(), true)), sprintf('sfCacophonyPlugin/%s', $provider));
    
    $this->getContext()->getConfiguration()->loadHelpers('Url');
    
    $query_params = array(
      'client_id'     => $config['providers'][$provider]['consumer_key'],
      'redirect_uri'  => $this->getContext()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for(sprintf('@sf_cacophony_callback?provider=%s', $provider), true) : 'oob',
    );
    
    if (isset($config['providers'][$provider]['response_type'])) $query_params['response_type'] = $config['providers'][$provider]['response_type'];
    if (isset($config['providers'][$provider]['scope'])) $query_params['scope'] = $config['providers'][$provider]['scope'];
    
    if ('facebook' == $provider) 
    {
      $query_params['state'] = $this->getUser()->getAttribute('state', null , sprintf('sfCacophonyPlugin/%s', $provider));
    }
    
    $this->redirect(
      sprintf(
        '%s?%s',
        $config['providers'][$provider]['authorize_url'],
        http_build_query($query_params)
      )
    );
  }
  
  /**
   * Oath 2.0 callback
   * 
   * @param sfWebRequest $request
   * @throws Exception
   */
  public function executeCallback2(sfWebRequest $request)
  {
    $config     = sfConfig::get('app_cacophony');
    $provider = $request->getParameter('provider');
    
    if ($request->hasParameter('state'))
    {
      // CSFR protection as adviced on the http://developers.facebook.com/docs/authentication/
      if ($request->getParameter('state') != $this->getUser()->getAttribute('state', null , sprintf('sfCacophonyPlugin/%s', $provider)))
      {
        throw new Exception('CSRF attack detected');
      }  
    }

    if (!$this->getUser()->isAuthenticated() || $config['plugin']['allow_multiple_tokens'])
    {
      try
      {
        $this->getUser()->setAttribute(
          'accessToken',
          sfCacophonyOAuth::getAccessToken2($provider, $request->getParameter('code')),
          sprintf('sfCacophonyPlugin/%s', $provider)
        );

        // add me to session
        $me = sfCacophonyOAuth::getMe(
          $provider,
          $this->getUser()->getAttribute('accessToken', null, sprintf('sfCacophonyPlugin/%s', $provider))
        );

        $this->getUser()->setAttribute('me', $me['normalized'], sprintf('sfCacophonyPlugin/%s', $provider));
      }
      catch (Exception $e)
      {
        // $this->getUser()->setFlash('error', sprintf('Failed to retrieve access token: %s', $e->getMessage()));
        // $this->redirect('@homepage');
        throw $e;
      }
    }
    else $this->redirect('@homepage');
    
    return sfView::NONE;
  }
  
  /**
   * Function to hook into on local project so don't have to rewrite entire register functionality
   * 
   * @param sfGuardUser $sf_guard_user
   * @param Token $token
   */
  public function postCreateHook($sf_guard_user, $token)
  {
    // Implement locally
  }
  
  /**
   * Function to hook into on local project so don't have to rewrite entire register functionality
   * 
   * @param sfGuardUser $sf_guard_user
   * @param Token $token
   */
  public function postUpdateHook($sf_guard_user, $token)
  {
    // Implement locally
  }
}