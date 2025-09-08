<?php

namespace Jonston\SanctumBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Jonston\SanctumBundle\Service\TokenService;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);

        $accessToken = $this->tokenService->findValidToken($token);

        if (!$accessToken) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        $tokenable = $this->tokenService->getTokenable($accessToken);

        return new SelfValidatingPassport(
            new UserBadge(
                (string) $tokenable->getTokenableId(),
                fn() => new UserAdapter($tokenable)
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Возвращаем null - это означает "продолжить обработку запроса"
        // Можно добавить логику обновления last_used_at для токена

        /** @var UserAdapter $user */
        $user = $token->getUser();
        $this->tokenService->updateLastUsed($user->getTokenable());

        return null; // продолжаем к контроллеру
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): string
    {
        $authHeader = $request->headers->get('Authorization');

        if ( ! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Invalid authorization header format');
        }

        $token = substr($authHeader, 7);

        if (empty(trim($token))) {
            throw new CustomUserMessageAuthenticationException('Token is empty');
        }

        return trim($token);
    }
}