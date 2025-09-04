# Symfony Sanctum Bundle

Simple token-based authentication for Symfony, inspired by Laravel Sanctum. Provides easy API token authentication without OAuth complexity.

## Features

- ✅ Token-based API authentication
- ✅ Personal access token creation and management
- ✅ Automatic token verification from HTTP headers
- ✅ Token expiration support
- ✅ Last usage tracking
- ✅ Simple Symfony Security integration

## Requirements

- PHP 8.1+
- Symfony 6.0+ or 7.0+
- Doctrine ORM 2.0+

## Installation

### 1. Install via Composer

```bash
composer require jonston/symfony-sanctum-bundle
```

### 2. Register the Bundle

In `config/bundles.php`:

```php
return [
    // ...
    Jonston\SanctumBundle\SanctumBundle::class => ['all' => true],
];
```

### 3. Create Database Table

```bash
# Create migration based on Entity
php bin/console doctrine:migrations:diff

# Run migration  
php bin/console doctrine:migrations:migrate

# Or update schema directly (for development)
php bin/console doctrine:schema:update --force
```

### 4. Configure Security

In `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
            
        main:
            # Your main configuration
```

## Usage

### Creating Tokens

```php
<?php

use Jonston\SanctumBundle\Service\TokenManager;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiController extends AbstractController
{
    public function __construct(
        private readonly TokenManager $tokenManager
    ) {}

    #[Route('/api/tokens', methods: ['POST'])]
    public function createToken(UserInterface $user): JsonResponse 
    {
        // Create token without expiration
        $result = $this->tokenManager->createToken($user, 'Mobile App');
        
        // Create token with expiration
        $expiresAt = new \DateTimeImmutable('+30 days');
        $result = $this->tokenManager->createToken($user, 'Web App', $expiresAt);
        
        return new JsonResponse([
            'token' => $result['token'], // Give this token to client
            'name' => $result['entity']->getName(),
            'expires_at' => $result['entity']->getExpiresAt()?->format('Y-m-d H:i:s')
        ]);
    }
}
```

### Using Tokens

Client should send token in `Authorization` header:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     http://localhost:8000/api/user
```

### Managing Tokens

```php
<?php

class TokenController extends AbstractController
{
    public function __construct(
        private readonly TokenManager $tokenManager
    ) {}

    // Get all tokens for current user
    #[Route('/api/tokens', methods: ['GET'])]
    public function getUserTokens(): JsonResponse
    {
        $user = $this->getUser();
        $tokens = $this->tokenManager->getUserTokens($user);
        
        return new JsonResponse(array_map(function($token) {
            return [
                'id' => $token->getId(),
                'name' => $token->getName(),
                'last_used_at' => $token->getLastUsedAt()?->format('Y-m-d H:i:s'),
                'expires_at' => $token->getExpiresAt()?->format('Y-m-d H:i:s'),
            ];
        }, $tokens));
    }

    // Revoke specific token
    #[Route('/api/tokens/{id}', methods: ['DELETE'])]
    public function revokeToken(int $id, PersonalAccessTokenRepository $repository): JsonResponse
    {
        $token = $repository->find($id);
        
        if (!$token || $token->getUserId() !== (int) $this->getUser()->getUserIdentifier()) {
            throw $this->createNotFoundException();
        }
        
        $this->tokenManager->revokeToken($token);
        
        return new JsonResponse(['message' => 'Token revoked']);
    }

    // Revoke all user tokens  
    #[Route('/api/tokens', methods: ['DELETE'])]
    public function revokeAllTokens(): JsonResponse
    {
        $this->tokenManager->revokeAllTokensForUser($this->getUser());
        
        return new JsonResponse(['message' => 'All tokens revoked']);
    }
}
```

### Cleaning Expired Tokens

Create a command for regular cleanup:

```php
<?php

// src/Command/CleanupTokensCommand.php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Jonston\SanctumBundle\Service\TokenManager;

class CleanupTokensCommand extends Command
{
    protected static $defaultName = 'sanctum:cleanup';
    
    public function __construct(private readonly TokenManager $tokenManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->tokenManager->cleanupExpiredTokens();
        $output->writeln("Removed {$count} expired tokens");
        
        return Command::SUCCESS;
    }
}
```

Run on schedule:

```bash
# Manually
php bin/console sanctum:cleanup

# Or add to crontab
0 2 * * * /path/to/your/app/bin/console sanctum:cleanup
```

## Protecting Routes

### Basic Protection

```php
#[Route('/api/user', methods: ['GET'])]  
#[IsGranted('IS_AUTHENTICATED')]
public function getUser(): JsonResponse
{
    return new JsonResponse([
        'id' => $this->getUser()->getUserIdentifier(),
        'email' => $this->getUser()->getEmail(),
    ]);
}
```

### Configuration-based

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED }
```

## API Reference

### TokenManager

#### `createToken(UserInterface $user, string $name, ?\DateTimeImmutable $expiresAt = null): array`

Creates a new token for user.

**Parameters:**
- `$user` - user to create token for
- `$name` - token name (e.g., "Mobile App")
- `$expiresAt` - expiration date (optional)

**Returns:** array with `token` (string for client) and `entity` (database object) keys

#### `revokeToken(PersonalAccessToken $token): void`

Removes specified token.

#### `revokeAllTokensForUser(UserInterface $user): void`

Removes all user tokens.

#### `getUserTokens(UserInterface $user): array`

Returns all active user tokens.

#### `cleanupExpiredTokens(): int`

Removes all expired tokens. Returns count of deleted records.

### PersonalAccessToken Entity

#### Main methods:

- `getName(): ?string` - token name
- `getCreatedAt(): ?\DateTimeImmutable` - creation date
- `getExpiresAt(): ?\DateTimeImmutable` - expiration date
- `getLastUsedAt(): ?\DateTimeImmutable` - last usage
- `getUserId(): ?int` - user ID
- `isExpired(): bool` - expiration check

## Security

### Token Hashing

Tokens are stored in database as SHA-256 hash. Original token is only visible at creation time.

### Protection from Attacks

- Use HTTPS in production
- Regularly clean expired tokens
- Set reasonable token expiration times
- Monitor suspicious activity

## Best Practices

### Token Naming

Give tokens meaningful names:

```php
$tokenManager->createToken($user, 'iPhone App - John');
$tokenManager->createToken($user, 'CI/CD Pipeline');
$tokenManager->createToken($user, 'Postman Testing');
```

### Expiration Management

```php
// Short tokens for automated systems
$shortTerm = new \DateTimeImmutable('+1 hour');
$tokenManager->createToken($user, 'CI Build', $shortTerm);

// Long tokens for mobile apps  
$longTerm = new \DateTimeImmutable('+90 days');
$tokenManager->createToken($user, 'Mobile App', $longTerm);
```

## Troubleshooting

### Token Not Accepted

1. Check header format: `Authorization: Bearer YOUR_TOKEN`
2. Verify token hasn't expired
3. Check firewall configuration in security.yaml

### User Not Loading

1. Ensure UserProvider is configured correctly
2. Check that user_id in token matches actual user ID
3. Verify user exists in system

## Testing

Run the test suite:

```bash
# Install dev dependencies
composer install --dev

# Run tests
./vendor/bin/phpunit

# Run tests with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## License

MIT

## Support

- GitHub Issues: https://github.com/jonston/symfony-sanctum-bundle/issues
- Documentation: https://github.com/jonston/symfony-sanctum-bundle/wiki