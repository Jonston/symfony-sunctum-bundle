# Symfony Sanctum Bundle

A Symfony bundle that provides Laravel Sanctum-like personal access token authentication.

## Description

This bundle allows you to easily integrate token-based authentication into Symfony applications. It provides functionality similar to Laravel Sanctum for creating and managing personal access tokens.

## Features

- ✅ Personal access token creation
- ✅ Token-based authentication via Bearer tokens
- ✅ Token lifecycle management
- ✅ Last used time tracking
- ✅ Token expiration support
- ✅ Symfony Security component integration
- ✅ Support for any entities via TokenableInterface

## Requirements

- PHP 8.2 or higher
- Symfony 6.0+ or 7.0+
- Doctrine ORM 2.14+ or 3.0+
- Doctrine Bundle 2.8+

## Installation

Install the bundle via Composer:

```bash
composer require jonston/symfony-sanctum-bundle
```

Add the bundle to `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Jonston\SanctumBundle\SanctumBundle::class => ['all' => true],
];
```

Create and run migrations for the tokens table:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Configuration

### 1. Security Configuration

Add the authenticator to `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
```

### 2. User Entity Configuration

Implement the `TokenableInterface` in your user entity:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Traits\TokenableTrait;

#[ORM\Entity]
class User implements TokenableInterface
{
    use TokenableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $email = null;

    // ... other fields and methods

    public function getId(): ?int
    {
        return $this->id;
    }

    // TokenableInterface methods are already implemented in TokenableTrait
}
```

## Usage

### Creating Tokens

```php
<?php

namespace App\Controller;

use Jonston\SanctumBundle\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    #[Route('/api/tokens', methods: ['POST'])]
    public function createToken(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser(); // Get authenticated user

        $token = $this->tokenService->createToken($user, 'API Token');

        return new JsonResponse([
            'token' => $token->getPlainTextToken(),
            'name' => $token->getName(),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }
}
```

### Using Tokens in Requests

Send the token in the Authorization header:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Content-Type: application/json" \
     http://your-app.com/api/protected-endpoint
```

### Getting Authenticated User

In API controllers:

```php
#[Route('/api/profile', methods: ['GET'])]
public function profile(): JsonResponse
{
    /** @var \Jonston\SanctumBundle\Security\UserAdapter $userAdapter */
    $userAdapter = $this->getUser();
    
    $tokenable = $userAdapter->getTokenable(); // Your user entity
    
    return new JsonResponse([
        'id' => $tokenable->getTokenableId(),
        'type' => $tokenable->getTokenableType()
    ]);
}
```

### Token Management

```php
// Get all user tokens
$tokens = $this->tokenService->getTokensFor($user);

// Revoke a token
$this->tokenService->revokeToken($token);

// Update last used time
$this->tokenService->updateLastUsed($user);
```

## Architecture

### Core Components

1. **PersonalAccessToken** - Entity for storing tokens
2. **TokenService** - Main service for token operations
3. **TokenHasher** - Service for token hashing and generation
4. **TokenAuthenticator** - Symfony Security authenticator
5. **UserAdapter** - Adapter for Symfony Security integration
6. **TokenableInterface** - Interface for entities that can have tokens
7. **TokenableTrait** - Trait with basic interface implementation

### Database

Structure of the `personal_access_tokens` table:

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key |
| name | string(255) | Token name |
| token | string(64) | Hashed token (indexed) |
| tokenable_type | string(255) | Entity type |
| tokenable_id | string(255) | Entity ID |
| created_at | datetime_immutable | Creation time |
| expires_at | datetime_immutable | Expiration time (nullable) |
| last_used_at | datetime_immutable | Last used time (nullable) |

## Security

- Tokens are hashed using SHA-256
- Expiration time checking is supported
- Last used time is tracked
- Cryptographically secure random generation for tokens

## Testing

Run tests:

```bash
vendor/bin/phpunit
```

## Extending Functionality

### Custom TokenHasher

```php
<?php

namespace App\Service;

use Jonston\SanctumBundle\Service\TokenHasher;

class CustomTokenHasher extends TokenHasher
{
    public function generatePlainToken(): string
    {
        // Your token generation logic
        return parent::generatePlainToken();
    }

    public function hashToken(string $plainToken): string
    {
        // Your hashing logic
        return parent::hashToken($plainToken);
    }
}
```

### Custom Authentication Logic

You can extend `TokenAuthenticator` to add additional logic:

```php
<?php

namespace App\Security;

use Jonston\SanctumBundle\Security\TokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;

class CustomTokenAuthenticator extends TokenAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // Additional validation logic
        return parent::supports($request);
    }
}
```

## License

MIT License

## Author

Eugene (eugene@example.com)

## Support

If you have questions or suggestions, please create an issue in the project repository.
