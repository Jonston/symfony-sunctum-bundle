# Symfony Sanctum Bundle

[![Latest Version](https://img.shields.io/packagist/v/jonston/symfony-sanctum-bundle.svg)](https://packagist.org/packages/jonston/symfony-sanctum-bundle)
[![License](https://img.shields.io/packagist/l/jonston/symfony-sanctum-bundle.svg)](https://packagist.org/packages/jonston/symfony-sanctum-bundle)

A bundle for generating and managing access tokens (AccessToken) in Symfony. Inspired by Laravel Sanctum, it provides a flexible architecture for linking tokens to any owner entities without modifying their source code.

## Features

- 🔧 **Flexible architecture** – dynamic relationship configuration via Doctrine MetadataListener
- 🔒 **Security** – tokens are hashed before being stored in the database
- ⏰ **Lifetime management** – support for tokens with limited validity
- 🎯 **Easy integration** – minimal changes to existing code
- 🧹 **Automatic cleanup** – command for removing expired tokens
- 🔐 **Authentication** – ready-to-use authenticator for Symfony Security

## Installation

```bash
composer require jonston/symfony-sanctum-bundle
```

## Configuration

Create the file `config/packages/sanctum.yaml`:

```yaml
sanctum:
    # Owner entity class (required)
    owner_class: App\Entity\User
    
    # Token length (default: 40)
    token_length: 40
    
    # Default expiration in hours (null = unlimited)
    default_expiration_hours: 24
```

## User Entity Setup

To use the bundle, you must:
- Implement the `HasAccessTokensInterface` in your user entity
- Use the `HasAccessTokensTrait` for token management
- Add the `accessTokens` property with a OneToMany annotation
- Configure the `owner` relationship in AccessToken via ManyToOne

### Example Implementation

```php
<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Traits\HasAccessTokensTrait;

#[ORM\Entity]
class User implements HasAccessTokensInterface
{
    use HasAccessTokensTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, AccessToken>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: AccessToken::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $accessTokens;

    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // ... other entity methods
}
```

**In AccessToken:**

```php
#[ORM\ManyToOne(targetEntity: HasAccessTokensInterface::class, inversedBy: 'accessTokens')]
private ?HasAccessTokensInterface $owner = null;
```

**Important notes:**
- The OneToMany relationship between User and AccessToken is configured via the `owner` field in AccessToken.
- Token management methods are implemented via the trait.
- Implement other entity methods as needed.

## Usage

### Creating tokens

```php
<?php

namespace App\Controller;

use App\Entity\User;
use Jonston\SanctumBundle\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    public function login(User $user): JsonResponse
    {
        // Create a token without expiration
        $result = $this->tokenService->createToken($user);
        $token = $result['plainTextToken'];

        return new JsonResponse([
            'token' => $token,
            'expires_at' => null
        ]);
    }

    public function createLimitedToken(User $user): JsonResponse
    {
        // Create a token with 1 hour expiration
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $result = $this->tokenService->createToken($user, $expiresAt);
        $token = $result['plainTextToken'];
        $accessToken = $result['accessToken'];

        return new JsonResponse([
            'token' => $token,
            'expires_at' => $accessToken->getExpiresAt()->format('Y-m-d H:i:s')
        ]);
    }
}
```

### Security configuration

In `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
```

### Usage in controllers

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiController extends AbstractController
{
    public function profile(): JsonResponse
    {
        $user = $this->getUser(); // UserAdapter
        $tokenOwner = $user->getTokenOwner(); // Your User entity

        return new JsonResponse([
            'id' => $tokenOwner->getId(),
            'email' => $tokenOwner->getEmail(),
        ]);
    }
}
```

### Revoking tokens

```php
public function logout(TokenService $tokenService): JsonResponse
{
    $token = $request->headers->get('Authorization');
    $token = substr($token, 7); // Remove "Bearer "
    
    $tokenService->revokeToken($token);
    
    return new JsonResponse(['message' => 'Token revoked']);
}

public function revokeAllTokens(User $user, TokenService $tokenService): JsonResponse
{
    $tokenService->revokeAllTokens($user);
    
    return new JsonResponse(['message' => 'All tokens revoked']);
}
```

## Commands

### Prune expired tokens

```bash
php bin/console sanctum:prune-expired
```

It is recommended to schedule this command via cron:

```bash
# Run every hour
0 * * * * cd /path/to/project && php bin/console sanctum:prune-expired
```

## Multiple token owners

To support multiple token owner types, create an abstract base class:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Jonston\SanctumBundle\Traits\HasAccessTokensTrait;

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['user' => User::class, 'client' => Client::class])]
abstract class TokenOwner implements HasAccessTokensInterface
{
    use HasAccessTokensTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

Then inherit your entities from this class:

```php
#[ORM\Entity]
class User extends TokenOwner
{
    // User-specific fields and methods
}

#[ORM\Entity]
class Client extends TokenOwner
{
    // Client-specific fields and methods
}
```

And update the configuration:

```yaml
sanctum:
    owner_class: App\Entity\TokenOwner
```

## Requirements

- PHP 8.1+
- Symfony 6.0+
- Doctrine ORM

## License

MIT License. See [LICENSE](LICENSE) for details.
