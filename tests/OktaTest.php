<?php

class OktaTest extends FunctionalTest
{

    /**
     * @var string
     */
    public static $fixture_file = 'okta/tests/fixtures/PageTest.yml';

    /**
     * Using to prevent BasePage from requiring in JS files that do not exist.
     *
     * @var bool
     */
    public static $disable_themes = true;

    /**
     * @var Okta
     */
    public $okta;


    public function setUp()
    {
        parent::setUp();

        $this->okta = Injector::inst()->create('Okta');
    }

    /**
     * redirect to IDP URL when trying to access page
     */
    public function testOktaSso()
    {
        if (!extension_loaded('Xdebug')) {
            $this->markTestSkipped('The Xdebug extension is not available.');
        }

        $page = $this->objFromFixture('Page', 'test-page');
        $page->publish('Stage', 'Live');

        $this->get($page->Link());
        $headers = xdebug_get_headers();

        $this->assertEquals('Location: ' . SS_OKTA_IDP_LOGIN_URL, $headers[1]);
    }

    /**
     * trying to logout from okta
     */
    public function testGetlogoutUrl()
    {
        $url = $this->okta->getLogoutUrl();

        $this->assertContains(SS_OKTA_IDP_LOGOUT_URL, $url);
    }

    /**
     * trying to login without saml session data
     */
    public function testUserWithoutSessionData()
    {
        $login = $this->okta->isLoggedIn();

        $this->assertFalse($login);
    }

    /**
     * check with already  SS logged user without saml session data
     */
    public function testLoggedUserWithoutSessionData()
    {
        $this->logInAs(1);
        $login = $this->okta->isLoggedIn();

        $this->assertFalse($login);
    }

    /**
     *  check with proper saml data
     */
    public function testUserWithSessionData()
    {
        $data = [
            'FirstName' => ['first name'],
            'Surname'   => ['surname'],
            'Email'     => ['myemail@abc.com'],
            'Login'     => 'myemail@abc.com',
            'SID'       => 'S-1-157275455'
        ];
        Session::set('samlUserdata', $data);
        Session::set('samlNameId', 'myemail@abc.com');
        Session::set('samlSessionIndex', 'id1494472649231.1217840392');

        $login = $this->okta->isLoggedIn();

        $this->assertTrue($login);
    }

    /**
     * check with invalid email
     */
    public function testUserWithSessionDataInvalidEmail()
    {
        $data = [
            'FirstName' => ['first name'],
            'Surname'   => ['surname'],
            'Email'     => ['invalid email'],
            'Login'     => 'invalid email',
            'SID'       => 'S-1-157275455'
        ];
        Session::set('samlUserdata', $data);
        Session::set('samlNameId', 'invalid email');
        Session::set('samlSessionIndex', 'id1494472649231.1217840392');

        try {
            $this->okta->isLoggedIn();
        } catch (Exception $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertEquals("Email must be a valid email address: invalid email", $e->getMessage());
        }
    }

    /**
     * check whitelisted when its not set
     */
    public function testCheckCurrentIPWithoutSettingWhiteList()
    {
        if (defined('SS_OKTA_IP_WHITELIST')) {
            $this->markTestSkipped('The SS_OKTA_IP_WHITELIST has been defined so cannot run this test.');
        }

        $reqFilter = Injector::inst()->create('OktaRequestFilter');

        $reflector = new ReflectionObject($reqFilter);

        $method = $reflector->getMethod('isWhitelisted');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($reqFilter));
    }

    /**
     * check IP whitelisted when its set
     */
    public function testCheckCurrentIPInWhiteList()
    {
        if (!defined('SS_OKTA_IP_WHITELIST')) {
            define('SS_OKTA_IP_WHITELIST', $_SERVER['REMOTE_ADDR']);
        }

        $reqFilter = Injector::inst()->create('OktaRequestFilter');

        $reflector = new ReflectionObject($reqFilter);

        $method = $reflector->getMethod('isWhitelisted');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($reqFilter));
    }

    /**
     * check time session when its not set
     */
    public function testTimeSession()
    {
        $reqFilter = Injector::inst()->create('OktaRequestFilter');

        $reflector = new ReflectionObject($reqFilter);

        $method = $reflector->getMethod('isTimeLimitedSession');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($reqFilter));
    }

    /**
     * check time session when its set
     */
    public function testCheckSetTimeLimit()
    {
        define('SS_SESSION_TIMELIMIT_WHITELIST', $_SERVER['REMOTE_ADDR']);
        $reqFilter = Injector::inst()->create('OktaRequestFilter');

        $reflector = new ReflectionObject($reqFilter);

        $method = $reflector->getMethod('isTimeLimitedSession');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($reqFilter));
    }

    /**
     * check URL whitelisted when its set
     */
    public function testCheckURLWhiteList()
    {
        $page = $this->objFromFixture('Page', 'test-page');
        $page->publish('Stage', 'Live');

        $request = new SS_HTTPRequest('get', $page->Link());

        $job = Injector::inst()->create('OktaRequestFilter');

        $method = new ReflectionMethod('OktaRequestFilter', 'isURLWhiteListed');
        $method->setAccessible(true);

        // check the isURLWhiteListed function before set the URL
        $this->assertFalse($method->invokeArgs($job, [$request]));

        // remove already added urls and add new url for test
        Config::inst()->remove('OktaRequestFilter', 'okta_whitelist_urls');
        Config::inst()->update('OktaRequestFilter', 'okta_whitelist_urls', [
            'test-page-title'
        ]);

        // check the isURLWhiteListed function after set url to whitelisted urls
        $this->assertTrue($method->invokeArgs($job, [$request]));
    }
}
