<?php

namespace Jonston\SanctumBundle\Security;

use Jonston\SanctumBundle\Service\TokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function supports(Request $request): ?bool
    {
        return (bool) preg_match('/^Bearer\s+\S+$/', (string) $request->headers->get('Authorization'));
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);
        $accessToken = $this->tokenService->findValidToken($token);

        if ( ! $accessToken) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired API token');
        }

        $this->tokenService->updateLastUsed($accessToken);
        $tokenOwner = $this->tokenService->getTokenOwner($accessToken);

        return new SelfValidatingPassport(
            new UserBadge(
                (string) $tokenOwner->getId(),
                fn() => $tokenOwner
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): string
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
            throw new CustomUserMessageAuthenticationException('No valid Bearer token provided');
        }
        return $matches[1];
    }
}
