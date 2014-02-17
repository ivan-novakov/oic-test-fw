<?php

namespace InoOicTestSuite;

use InoOicClient\Client\ClientInfo;
use InoOicClient\Oic\Authorization\State;
use InoOicClient\Oic\Authorization;
use InoOicClient\Http\ClientFactory;
use InoOicClient\Oic\Token;
use InoOicClient\Oic\UserInfo;


class SimpleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Zend\Http\Client
     */
    protected $httpClient;

    /**
     * @var ClientInfo
     */
    protected $clientInfo;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $state;


    public function testAuthorize()
    {
        $clientInfo = $this->getClientInfo();
        
        $stateManager = new State\Manager();
        
        $dispatcher = new Authorization\Dispatcher();
        $dispatcher->setStateManager($stateManager);
        
        $request = new Authorization\Request($clientInfo, 'code', 'openid profile email');
        
        $uri = $dispatcher->createAuthorizationRequestUri($request);
        
        $client = $this->getHttpClient();
        
        $userInfo = $this->getConfig('user_info');
        $client->setAuth($userInfo['username'], $userInfo['password']);
        
        $request = new \Zend\Http\Request();
        $request->setUri($uri);
        $response = $client->send($request);
        
        $locationHeader = $response->getHeaders()->get('Location');
        $this->assertInstanceOf('Zend\Http\Header\Location', $locationHeader);
        
        /* @var $locationHeader \Zend\Http\Header\Location */
        $uri = new \Zend\Uri\Uri($locationHeader->getUri());
        $queryVars = $uri->getQueryAsArray();
        
        $this->assertArrayHasKey('code', $queryVars);
        $this->assertArrayHasKey('state', $queryVars);
        
        $stateManager->validateState($queryVars['state']);
        
        $cookies = $client->getCookies();
        $sessionCookieExists = false;
        foreach ($cookies as $setCookieHeader) {
            /* @var $setCookieHeader \Zend\Http\Header\SetCookie */
            $serverInfo = $this->getConfig('server_info');
            if ($serverInfo['session_cookie_name'] == $setCookieHeader->getName()) {
                $sessionCookieExists = true;
            }
        }
        
        $this->assertTrue($sessionCookieExists, sprintf("Missing session cookie '%s'", $serverInfo['session_cookie_name']));
        
        return array(
            'code' => $queryVars['code'],
            'state' => $queryVars['state']
        );
    }


    /**
     * @depends testAuthorize
     */
    public function testGetToken(array $context)
    {
        $this->assertNotNull($context['code'], '"code" should not be null');
        $this->assertNotNull($context['state'], '"state" should not be null');
        
        $tokenRequest = new Token\Request();
        $tokenRequest->fromArray(array(
            'client_info' => $this->getClientInfo(),
            'code' => $context['code'],
            'grant_type' => 'authorization_code'
        ));
        
        $tokenDispatcher = new Token\Dispatcher($this->getHttpClient());
        $tokenResponse = $tokenDispatcher->sendTokenRequest($tokenRequest);
        
        $this->assertNotNull($tokenResponse->getAccessToken());
        $this->assertSame('bearer', $tokenResponse->getTokenType());
        
        return array(
            'token' => $tokenResponse->getAccessToken()
        );
    }


    /**
     * @depends testGetToken
     */
    public function testGetUserInfo(array $context)
    {
        $this->assertNotNull($context['token']);
        
        $userInfoRequest = new UserInfo\Request();
        $userInfoRequest->setAccessToken($context['token']);
        $userInfoRequest->setClientInfo($this->getClientInfo());
        
        $userInfoDispatcher = new UserInfo\Dispatcher($this->getHttpClient());
        $userInfoResponse = $userInfoDispatcher->sendUserInfoRequest($userInfoRequest);
        $claims = $userInfoResponse->getClaims();
        
        $this->assertSame('testuser@example.org', $claims['id']);
    }
    
    /*
     * ---------------------------------
     */
    protected function getHttpClient()
    {
        if (null === $this->httpClient) {
            $clientFactory = new ClientFactory();
            $this->httpClient = $clientFactory->createHttpClient();
        }
        
        return $this->httpClient;
    }


    protected function getClientInfo()
    {
        if (null === $this->clientInfo) {
            $this->clientInfo = new ClientInfo();
            $this->clientInfo->fromArray($this->getConfig('client_info'));
        }
        
        return $this->clientInfo;
    }


    protected function getConfig($index = null)
    {
        if (null === $this->config) {
            $this->config = require OIC_TEST_FW_ROOT . '/config/oic-test-fw.config.php';
        }
        
        if (null !== $index) {
            if (isset($this->config[$index])) {
                return $this->config[$index];
            }
            
            return null;
        }
        
        return $this->config;
    }
}