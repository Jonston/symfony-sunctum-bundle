# Symfony Sanctum Bundle

A Symfony bundle that provides Laravel Sanctum-like personal access token authentication.

## Installation

### 1. Install via Composer

```bash
composer require jonston/symfony-sanctum-bundle
```

### 2. Register the bundle

Add to `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Jonston\SanctumBundle\SanctumBundle::class => ['all' => true],
];
```

**That's it!** All services are automatically registered and ready to use.

## How it works

### Automatic Service Registration

The bundle automatically registers all necessary services:

- `TokenHasher` - handles token generation and hashing
- `TokenManager` - manages token lifecycle (create, revoke, cleanup)
- `PersonalAccessTokenRepository` - database operations
- `TokenAuthenticator` - handles API authentication

All services use **autowiring**, so Symfony automatically injects dependencies.

### Usage Example

In your controller, just type-hint the `TokenManager`:

```php
<?php

namespace App\Controller;

use Jonston\SanctumBundle\Service\TokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/create-token', methods: ['POST'])]
    public function createToken(TokenManager $tokenManager): JsonResponse
    {
        $user = $this->getUser(); // Your user that extends TokenizableUser
        
        $result = $tokenManager->createToken($user, 'API Token');
        
        return $this->json([
            'token' => $result['token'] // Return this to the user
        ]);
    }
}
```

**No manual DI configuration needed!** Symfony automatically injects `TokenManager` with all its dependencies (`TokenHasher`, `EntityManager`, `Repository`).

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

### 2. Create User Entity

Extend `TokenizableUser`:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jonston\SanctumBundle\Entity\TokenizableUser;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User extends TokenizableUser
{
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    // Your custom fields...
}
```

### 3. Run Migrations

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## API Usage

### Authentication

Send token in Authorization header:

```
Authorization: Bearer {your-token-here}
```

### Token Management

```php
// Create token with expiration
$result = $tokenManager->createToken($user, 'Mobile App', new \DateTimeImmutable('+30 days'));

// Get user tokens
$tokens = $tokenManager->getUserTokens($user);

// Revoke token
$tokenManager->revokeToken($tokenEntity);

// Revoke all user tokens
$tokenManager->revokeAllTokensForUser($user);

// Clean expired tokens
$count = $tokenManager->cleanupExpiredTokens();
```

## Troubleshooting

### "TokenManager not found" error

Make sure you:

1. ✅ Registered the bundle in `config/bundles.php`
2. ✅ Cleared cache: `php bin/console cache:clear`
3. ✅ Have autowiring enabled in your `services.yaml`

### Services not auto-registering

The bundle uses Symfony's auto-configuration. If it's not working:

1. Check `config/services.yaml` has `autowire: true`
2. Verify bundle is in `config/bundles.php`
3. Clear cache and warmup: `php bin/console cache:clear && php bin/console cache:warmup`

## Architecture

- **TokenHasher**: Centralized token generation and hashing (SHA256)
- **TokenManager**: High-level token operations
- **PersonalAccessToken**: Simple data entity (no business logic)
- **TokenizableUser**: Base user class with token relationships
- **TokenAuthenticator**: Symfony security integration

Each class has a single responsibility, making the code maintainable and testable.
