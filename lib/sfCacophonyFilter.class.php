<?php

/**
 * sfCacophonyFilter
 * 
 * Redirects from callback function to user defined action
 * 
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyFilter
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyFilter extends sfFilter
{
  public function execute($filterChain)
  {
    if(
      $this->getContext()->getRequest()->getParameter('module') == 'sfCacophonyConsumer' &&
      $this->getContext()->getRequest()->getParameter('action') == 'callback'
    )
    {
      $filterChain->execute();
      $this->getContext()->getController()->redirect(
        sprintf('%s/%s?provider=%s',
          $this->getParameter('module') ?: 'sfCacophonyConsumer',
          $this->getParameter('action') ?: 'register',
          $this->getContext()->getRequest()->getParameter('provider')
        )
      );
    }
    else $filterChain->execute();
  }
}
