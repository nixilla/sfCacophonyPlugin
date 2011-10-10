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