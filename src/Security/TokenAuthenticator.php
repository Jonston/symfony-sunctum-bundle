<?php

namespace Jonston\SanctumBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;
use Jonston\SanctumBundle\Service\TokenManager;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly PersonalAccessTokenRepository $tokenRepository,
        private readonly TokenManager $tokenManager,
        private readonly ?UserProviderInterface $userProvider = null
    ) {}

    public function supports(Request $request): ?bool
    {
        $authorization = $request->headers->get('Authorization');
        return $authorization && str_starts_with($authorization, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = $request->headers->get('Authorization');
        $token = substr($authorization, 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $tokenHash = $this->tokenManager->hashToken($token);
        $personalAccessToken = $this->tokenRepository->findByToken($tokenHash);

        if (!$personalAccessToken) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        if ($personalAccessToken->isExpired()) {
            throw new CustomUserMessageAuthenticationException('API token expired');
        }

        $personalAccessToken->updateLastUsedAt();
        $this->tokenRepository->save($personalAccessToken);

        $userIdentifier = $personalAccessToken->getUser()->getUserIdentifier();

        $userBadge = new UserBadge(
            $userIdentifier,
            $this->userProvider ?
                fn($identifier) => $this->userProvider->loadUserByIdentifier($identifier) :
                fn($identifier) => $personalAccessToken->getUser()
        );

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessageKey()
        ], Response::HTTP_UNAUTHORIZED);
    }
}