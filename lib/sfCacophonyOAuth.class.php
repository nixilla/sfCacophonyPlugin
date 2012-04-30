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
 * @author     Jo Carter <jocarter@holler.co.uk>
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
      if (sfConfig::get('sf_logging_enabled')) sfContext::getInstance()->getLogger()->err($e->lastResponse);
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
   * @param $oauth_verifier
   * @throws OAuthException
   * @return array
   */
  public static function getAccessToken($provider,$oauth_token,$oauth_token_secret,$oauth_verifier)
  {
    $config = sfConfig::get('app_cacophony');
    
    $oauth = self::getInstance($provider);
    $oauth->setToken($oauth_token,$oauth_token_secret);
    
    if (sfConfig::get('sf_logging_enabled')) $oauth->enableDebug();
    
    try
    {
      return $oauth->getAccessToken($config['providers'][$provider]['access_token_url'], null, $oauth_verifier);
    }
    catch (OAuthException $e)
    {
      if (sfConfig::get('sf_logging_enabled'))
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
   * @throws Exception
   * @return OAuth
   */
  public static function getInstance($provider)
  {
    if (!$provider) throw new Exception('Missing provider information');
    
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
    $config = sfConfig::get('app_cacophony');

    return call_user_func(
      array($config['providers'][$provider]['sound'], 'getMe'),
      $accessToken,
      self::getInstance($provider)
    );
  }
  
  /**
   * Proxy method to providers' specific one
   * 
   * @param String $method - Provider's method to be called
   * @param string $provider
   * @param Array $accessToken
   * @param Array $params  - additional parameters required for Providers methods
   * @return mixed
   */
  public static function call($method, $provider, $accessToken = null, $params = array())
  {
    $config = sfConfig::get('app_cacophony');

    return call_user_func(
      array($config['providers'][$provider]['sound'], 'call'),
      $method,
      $accessToken,
      self::getInstance($provider),
      $params
    );
  }

  /**
   * @deprecated Upgraded to include more oauth 2.0 implementations
   * @param $code
   * @return mixed
   */
  public static function getFacebookToken($code)
  {
    return self::getAccessToken2('facebook', $code);
  }

  /**
   * Get OAuth 2.0 access token
   * For now Facebook and Instagram have different implementations.
   * When we have more than one using the same method, we can refactor
   *
   * @param $provider
   * @param string $code
   * @return mixed
   */
  public static function getAccessToken2($provider, $code)
  {
    $config = sfConfig::get('app_cacophony');

    return call_user_func(
      array($config['providers'][$provider]['sound'], 'getAccessToken'),
      $code,
      self::getInstance($provider)
    );
  }
}