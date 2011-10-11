<?php

include(sfConfig::get('sf_test_dir').'/bootstrap/functional.php');

$browser = new sfTestFunctional(new sfBrowser());

$config = sfConfig::get('app_cacophony');

$browser->
  info('Twitter Connect')->
  get('/oauth/connect/twitter')->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'connect')->
    isParameter('provider', 'twitter')->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()
;

$response = $browser->getResponse();

$browser->test()->like($response->getHttpHeader('Location'),'@'.$config['providers']['twitter']['authorize_url'].'@','Connect redirects to correct URL');
//$browser->info(parse_url($response->getHttpHeader('Location'), PHP_URL_QUERY));


$browser->
  info('Twitter Callback')->
  get('/oauth/callback/twitter?oauth_token=1234&oauth_verifier=5678')->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'callback')->
    isParameter('provider', 'twitter')->
    isParameter('oauth_token','1234')->
    isParameter('oauth_verifier','5678')->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()->
    
  with('user')->begin()->
    isFlash('error','Failed to retrieve access token: Invalid auth/bad request (got a 401, expected HTTP/1.1 20X or a redirect)')->
  end()
;


$browser->
  info('Vimeo Connect')->
  get('/oauth/connect/vimeo')->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'connect')->
    isParameter('provider', 'vimeo')->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()
;

$response = $browser->getResponse();

$browser->test()->like($response->getHttpHeader('Location'),'@'.$config['providers']['vimeo']['authorize_url'].'@','Connect redirects to correct URL');
//$browser->info(parse_url($response->getHttpHeader('Location'), PHP_URL_QUERY));

$browser->
  info('Vimeo Callback')->
  get('/oauth/callback/vimeo?oauth_token=1234&oauth_verifier=5678')->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'callback')->
    isParameter('provider', 'vimeo')->
    isParameter('oauth_token','1234')->
    isParameter('oauth_verifier','5678')->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()->
    
  with('user')->begin()->
    isFlash('error','Failed to retrieve access token: Invalid auth/bad request (got a 401, expected HTTP/1.1 20X or a redirect)')->
  end()
;


$browser->
  info('Facebook Connect')->
  get('/oauth/connect/facebook')->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'connect')->
    isParameter('provider', 'facebook')->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()
;

$response = $browser->getResponse();

$browser->test()->like($response->getHttpHeader('Location'),'@'.$config['providers']['facebook']['authorize_url'].'@','Connect redirects to correct URL');
//$browser->info(parse_url($response->getHttpHeader('Location'), PHP_URL_QUERY));
parse_str(parse_url(urldecode($response->getHttpHeader('Location')), PHP_URL_QUERY),$query_string);

$browser->
  info('Facebook Callback')->
  get(sprintf('/oauth/callback/facebook?code=1234&state=%s',$query_string['state']))->

  with('request')->begin()->
    isParameter('module', 'sfCacophonyConsumer')->
    isParameter('action', 'callback')->
    isParameter('provider', 'facebook')->
    isParameter('code','1234')->
    isParameter('state',$query_string['state'])->
  end()->

  with('response')->begin()->
    isStatusCode(302)->
  end()->
    
  with('user')->begin()->
    isFlash('error','Failed to retrieve access token: ')->
  end()
;