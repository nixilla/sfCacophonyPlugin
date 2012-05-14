<?php

/**
 * sfCacophonyMock actions.
 *
 * This module mocks the responses from various OAuth Providers
 *
 * @package    sfCacophonyPlugin
 * @subpackage sfCacophonyMock
 * @author     Janusz Slota <janusz.slota@nixilla.com>
 */
class sfCacophonyMockActions extends sfActions
{

  public function executeIndex(sfWebRequest $request)
  {
    if($request->getParameter('method') == 'dialog')
    {
      $this->redirect(sprintf(
        '@sf_cacophony_callback?provider=%s&code=1234&state=%s',
        $request->getParameter('provider'),
        $this->getUser()->getAttribute('state', null, sprintf('sfCacophonyPlugin/%s', $request->getParameter('provider')))
      ));
    }
  }
}
