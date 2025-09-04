# Symfony Sanctum Bundle

A Symfony bundle that provides Laravel Sanctum-like personal access token authentication.

## Installation

Install the bundle via Composer:

```bash
composer require jonston/symfony-sanctum-bundle
```

## Configuration

### 1. Register the bundle

Add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Jonston\SanctumBundle\SanctumBundle::class => ['all' => true],
];
```

### 2. Configure security

Add the authenticator to your `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
```

### 3. Create your User entity

Extend the `TokenizableUser` class:

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

    // Add your custom fields and methods
}
```

### 4. Run migrations

Create and run the migration for the personal access tokens table:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Usage

### Creating tokens

```php
use Jonston\SanctumBundle\Service\TokenManager;

public function createToken(TokenManager $tokenManager, User $user): array
{
    $result = $tokenManager->createToken($user, 'API Token');
    
    return [
        'token' => $result['token'], // Plain text token to return to user
        'entity' => $result['entity'] // PersonalAccessToken entity
    ];
}
```

### Token authentication

Send the token in the Authorization header:

```
Authorization: Bearer {your-token-here}
```

### Managing tokens

```php
// Get user tokens
$tokens = $tokenManager->getUserTokens($user);

// Revoke a specific token
$tokenManager->revokeToken($tokenEntity);

// Revoke all user tokens
$tokenManager->revokeAllTokensForUser($user);

// Clean up expired tokens
$count = $tokenManager->cleanupExpiredTokens();
```

## Features

- Secure token generation and hashing
- Token expiration support
- Automatic cleanup of expired tokens
- Seamless Symfony security integration
- Simple API for token management

## Requirements

- PHP 8.1+
- Symfony 6.0+
- Doctrine ORM
