<?php

class OktaController extends Page_Controller
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'sso',
        'slo',
        'loggedout',
    ];

    /**
     * Redirects to okta login
     */
    public function index()
    {
        $okta = Injector::inst()->create('Okta');
        return $this->redirect($okta->getLoginUrl());
    }

    /**
     * Performs okta and silverstripe login
     *
     * @param SS_HTTPRequest $request
     *
     * @return SS_HTPResponse
     */
    public function sso(SS_HTTPRequest $request)
    {
        $okta = Injector::inst()->create('Okta');
        $relay = $okta->getLoginUrl();

        // Attempt single sign on
        if ($okta->sso()) {
            $relay = $request->postVar('RelayState') ?: Director::baseUrl();
        }

        return $this->redirect($relay);
    }

    /**
     * Performs okta and silverstripe logout
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     * @throws OneLogin_Saml2_Error
     */
    public function slo(SS_HTTPRequest $request)
    {
        // Allows the user to see the loggedout page. We're not bothered about unsetting
        // this later as it only exists to protect the website from people who have not
        // logged in at all.
        Session::set('hasLoggedOut', true);

        try {
            $okta = Injector::inst()->create('Okta');
        } catch (OneLogin_Saml2_Error $e) {
            // if we're in dev we can redirect to the ss logout
            if (Director::isDev()) {
                return $this->redirect('/Security/Logout');
            }

            // if not in dev, just throw the error. Something has went wrong.
            throw $e;
        }

        if ($request->httpMethod() == 'POST') {
            $okta->slo();

            $this->clearSession();

            $url = Controller::join_links(Director::baseUrl(), 'okta', 'loggedout');
            return $this->redirect($url);
        }

        if (!empty(Session::get('samlNameId'))) {
            $logoutUrl = $okta->getLogoutUrl();
        } else {
            $logoutUrl = Director::baseUrl();
        }

        $this->clearSession();

        return $this->redirect($logoutUrl);
    }

    /**
     * @return HTMLText|SS_HTTPResponse
     */
    public function loggedout()
    {
        if (Member::currentUser()) {
            $url = Controller::join_links(Director::baseUrl(), 'okta', 'slo');
            return $this->redirect($url);
        }

        if (!Session::get('hasLoggedOut')) {
            return $this->redirect(Director::baseUrl());
        }

        $okta = Injector::inst()->create('Okta');
        $data = [
            'Title'        => 'Focused: Future',
            'OktaLoginUrl' => $okta->getLoginUrl(),
        ];

        return $this
            ->customise($data)
            ->renderWith(['Okta_loggedout', 'Page']);
    }

    /**
     * logout if your already logged in and
     * delete all the sessions after logout.
     */
    protected function clearSession()
    {
        $member = Member::currentUser();
        if ($member) {
            $member->logOut();
            Session::clear_all();
        }
    }

}
