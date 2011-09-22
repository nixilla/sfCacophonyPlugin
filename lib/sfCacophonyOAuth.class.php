<?php

/**
 * sfCacophonyConsumer actions.
 * 
 * This class uses Pecl OAuth class
 * @see http://uk.php.net/manual/en/class.oauth.php
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyOAuth
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyOAuth
{
  /**
   * Calls provider's OAuth request token service 
   * and returns whatever it gets from it
   * 
   * @param String $provider
   * @return Array
   */
  public static function getRequestToken($provider)
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Url'));
    
    $config = sfConfig::get('app_cacophony');
    $oauth = self::getInstance($provider);
    
    try
    {
      return $oauth->getRequestToken(
        $config['providers'][$provider]['request_token_url'],
        sfContext::getInstance()->getRouting()->hasRouteName('sf_cacophony_callback') ? url_for(sprintf('@sf_cacophony_callback?provider=%s',$provider),true) : 'oob'
      );
    }
    catch (OAuthException $e)
    {
      if(sfConfig::get('sf_logging_enabled')) sfContext::getInstance()->getLogger()->err($e->lastResponse);
      return false;
    }
  }
  
  /**
   * Calls provider's access token service
   * and returns whatever it gets from it
   * 
   * @param String $provider
   * @param String $oauth_token
   * @param String $oauth_token_secret 
   */
  public static function getAccessToken($provider,$oauth_token,$oauth_token_secret,$oauth_verifier)
  {
    $config = sfConfig::get('app_cacophony');
    
    $oauth = self::getInstance($provider);
    $oauth->setToken($oauth_token,$oauth_token_secret);
    
    if(sfConfig::get('sf_logging_enabled')) $oauth->enableDebug();
    
    try
    {
      return $oauth->getAccessToken($config['providers'][$provider]['access_token_url'], null, $oauth_verifier);
    }
    catch (OAuthException $e)
    {
      if(sfConfig::get('sf_logging_enabled'))
      {
        sfContext::getInstance()->getLogger()->err(sprintf('{OAuthException} %s',$e->lastResponse));
        sfContext::getInstance()->getLogger()->info(sprintf('{OAuthException} %s',print_r($oauth->debugInfo,true)));
      }
      throw $e;
    }
  }
  
  public static function refreshToken(SfGuardUser $user,$provider)
  {
    
  }
  
  /**
   *
   * @param string $provider
   * @return OAuth 
   */
  public static function getInstance($provider)
  {
    if( ! $provider) throw new Exception('Missing provider information');
    
    $config = sfConfig::get('app_cacophony');
    return new OAuth(
      $config['providers'][$provider]['consumer_key'],
      $config['providers'][$provider]['consumer_secret'],
      OAUTH_SIG_METHOD_HMACSHA1,
      OAUTH_AUTH_TYPE_URI
    );
  }
  
  /**
   *
   * @param String $provider
   * @param Array $accessToken
   * @return Array
   */
  public static function getMe($provider,$accessToken)
  {
    return call_user_func(
      array(
        sprintf('sfCacophony%sSound',  ucfirst($provider)),
        'getMe'),
      $accessToken,
      self::getInstance($provider)
    );
  }
  
  /**
   * Proxy method to providers' specific one
   * 
   * @param String $method
   * @param string $provider
   * @param Array $accessToken
   * @param Array $params 
   */
  public static function call($method, $provider, $accessToken, $params = array())
  {
    return call_user_func(
      array(
        sprintf('sfCacophony%sSound',  ucfirst($provider)),
        'call'),
      $method,
      $accessToken,
      self::getInstance($provider),
      $params
    );
  }
}
