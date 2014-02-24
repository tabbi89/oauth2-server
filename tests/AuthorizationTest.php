<?php

namespace LeagueTests;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\Storage\ScopeInterface;
use \Mockery as M;

class AuthorizationTests extends \PHPUnit_Framework_TestCase
{
    public function testGetExceptionMessage()
    {
        $m = AuthorizationServer::getExceptionMessage('access_denied');

        $reflector = new \ReflectionClass('League\OAuth2\Server\AuthorizationServer');
        $exceptionMessages = $reflector->getProperty('exceptionMessages');
        $exceptionMessages->setAccessible(true);
        $v = $exceptionMessages->getValue();

        $this->assertEquals($v['access_denied'], $m);
    }

    public function testGetExceptionCode()
    {
        $this->assertEquals('access_denied', AuthorizationServer::getExceptionType(2));
    }

    public function testGetExceptionHttpHeaders()
    {
        $this->assertEquals(array('HTTP/1.1 401 Unauthorized'), AuthorizationServer::getExceptionHttpHeaders('access_denied'));
        $this->assertEquals(array('HTTP/1.1 500 Internal Server Error'), AuthorizationServer::getExceptionHttpHeaders('server_error'));
        $this->assertEquals(array('HTTP/1.1 501 Not Implemented'), AuthorizationServer::getExceptionHttpHeaders('unsupported_grant_type'));
        $this->assertEquals(array('HTTP/1.1 400 Bad Request'), AuthorizationServer::getExceptionHttpHeaders('invalid_refresh'));
    }

    public function testSetGet()
    {
        $server = new AuthorizationServer;
        $server->requireScopeParam(true);
        $server->requireStateParam(true);
        $server->setDefaultScope('foobar');
        $server->setScopeDelimeter(',');
        $server->setAccessTokenTTL(1);

        $grant = M::mock('League\OAuth2\Server\Grant\GrantTypeInterface');
        $grant->shouldReceive('getIdentifier')->andReturn('foobar');
        $grant->shouldReceive('getResponseType')->andReturn('foobar');
        $grant->shouldReceive('setAuthorizationServer');

        $scopeStorage = M::mock('League\OAuth2\Server\Storage\ScopeInterface');
        $scopeStorage->shouldReceive('setServer');

        $server->addGrantType($grant);
        $server->setScopeStorage($scopeStorage);

        $this->assertTrue($server->hasGrantType('foobar'));
        $this->assertTrue($server->getGrantType('foobar') instanceof GrantTypeInterface);
        $this->assertSame($server->getResponseTypes(), ['foobar']);
        $this->assertTrue($server->scopeParamRequired());
        $this->assertTrue($server->stateParamRequired());
        $this->assertTrue($server->getStorage('scope') instanceof ScopeInterface);
        $this->assertEquals('foobar', $server->getDefaultScope());
        $this->assertEquals(',', $server->getScopeDelimeter());
        $this->assertEquals(1, $server->getAccessTokenTTL());
    }

    public function testInvalidGrantType()
    {
        $this->setExpectedException('League\OAuth2\Server\Exception\InvalidGrantTypeException');
        $server = new AuthorizationServer;
        $server->getGrantType('foobar');
    }

    public function testIssueAccessToken()
    {
        $grant = M::mock('League\OAuth2\Server\Grant\GrantTypeInterface');
        $grant->shouldReceive('getIdentifier')->andReturn('foobar');
        $grant->shouldReceive('getResponseType')->andReturn('foobar');
        $grant->shouldReceive('setAuthorizationServer');
        $grant->shouldReceive('completeFlow')->andReturn(true);

        $_POST['grant_type'] = 'foobar';

        $server = new AuthorizationServer;
        $server->addGrantType($grant);

        $this->assertTrue($server->issueAccessToken());
    }

    public function testIssueAccessTokenEmptyGrantType()
    {
        $this->setExpectedException('League\OAuth2\Server\Exception\ClientException');
        $server = new AuthorizationServer;
        $this->assertTrue($server->issueAccessToken());
    }

    public function testIssueAccessTokenInvalidGrantType()
    {
        $this->setExpectedException('League\OAuth2\Server\Exception\ClientException');

        $_POST['grant_type'] = 'foobar';

        $server = new AuthorizationServer;
        $this->assertTrue($server->issueAccessToken());
    }
}
