<?php

namespace Jonston\SanctumBundle\Tests\Unit\Security;

use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Security\TokenAuthenticator;
use Jonston\SanctumBundle\Service\TokenService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticatorTest extends TestCase
{
    private TokenAuthenticator $authenticator;
    private TokenService|MockObject $tokenService;
    private HasAccessTokensInterface|MockObject $tokenOwner;

    protected function setUp(): void
    {
        $this->tokenService = $this->createMock(TokenService::class);
        $this->tokenOwner = $this->createMock(HasAccessTokensInterface::class);
        $this->authenticator = new TokenAuthenticator($this->tokenService);
    }

    public function testSupportsReturnsTrueForValidBearerToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid-token-here');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForInvalidAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Basic username:password');

        $this->assertFalse($this->authenticator->supports($request));

        $request->headers->set('Authorization', 'Bearer');
        $this->assertFalse($this->authenticator->supports($request));

        $request->headers->remove('Authorization');
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid-token');

        $accessToken = $this->createMock(AccessToken::class);
        $this->tokenOwner->method('getId')->willReturn(123);

        $this->tokenService->expects($this->once())
            ->method('findValidToken')
            ->with('valid-token')
            ->willReturn($accessToken);

        $this->tokenService->expects($this->once())
            ->method('updateLastUsed')
            ->with($accessToken);

        $this->tokenService->expects($this->once())
            ->method('getTokenOwner')
            ->with($accessToken)
            ->willReturn($this->tokenOwner);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $userBadge);
        $this->assertEquals('123', $userBadge->getUserIdentifier());
        $this->assertSame($this->tokenOwner, $userBadge->getUserLoader()());
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $this->tokenService->expects($this->once())
            ->method('findValidToken')
            ->with('invalid-token')
            ->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired API token');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithMalformedAuthorizationHeader(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No valid Bearer token provided');

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsJsonResponse(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Test error message');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Test error message', $content['message']);
    }
}
