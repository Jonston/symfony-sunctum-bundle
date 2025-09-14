# Symfony Sanctum Bundle

[![Latest Version](https://img.shields.io/packagist/v/jonston/symfony-sanctum-bundle.svg)](https://packagist.org/packages/jonston/symfony-sanctum-bundle)
[![License](https://img.shields.io/packagist/l/jonston/symfony-sanctum-bundle.svg)](https://packagist.org/packages/jonston/symfony-sanctum-bundle)

A bundle for generating and managing access tokens (AccessToken) in Symfony. Inspired by Laravel Sanctum, it provides a flexible architecture for linking tokens to any owner entities without modifying their source code.

## Table of contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [User Entity Setup](#user-entity-setup)
- [Usage](#usage)
  - [Creating tokens](#creating-tokens)
  - [Security configuration](#security-configuration)
  - [Usage in controllers](#usage-in-controllers)
  - [Revoking tokens](#revoking-tokens)
- [Commands](#commands)
- [Multiple token owners](#multiple-token-owners)
- [What the package publishes and why](#what-the-package-publishes-and-why)
- [Requirements](#requirements)
- [License](#license)

## Features

- 🔧 **Flexible architecture** – dynamic relationship configuration via Doctrine
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

⚠️ Note: by default the bundle uses the App\Entity\User class as the owner of access tokens for the AccessToken `owner` mapping. If you want to override this and use your own entity, create a configuration file (config/packages/sanctum.yaml) and set the `owner_class` parameter to your entity class. When `owner_class` is provided the bundle will prepend a `resolve_target_entities` entry mapping `Jonston\SanctumBundle\Contract\HasAccessTokensInterface` to your class so Doctrine can correctly map the interface to your entity.

Create the file `config/packages/sanctum.yaml` (the recipe publishes a sample):

```yaml
sanctum:
    # Owner entity class
    owner_class: App\Entity\User
    
    # Token length (default: 40)
    token_length: 40
    
    # Default expiration in hours (null = unlimited)
    default_expiration_hours: 24
```

## User Entity Setup

To use the bundle, you must:
- Implement the `HasAccessTokensInterface` in your owner entity
- Use the `HasAccessTokensTrait` for token management (optional helper)
- Add the `accessTokens` property with a OneToMany annotation (if you want a bidirectional relation)
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

**⚠️ Important notes:**
- The OneToMany relationship between owner and AccessToken is configured via the `owner` field in AccessToken.
- Token management methods are implemented via the trait; you may implement them manually if preferred.

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

Below is an example `security.yaml` configuration for an API route group using the bundle's custom TokenAuthenticator. It enables the new authenticator manager, registers a firewall that matches routes starting with `/api`, marks the firewall as stateless and uses the custom authenticator. You can allow anonymous access to specific endpoints (e.g. login) by adding an access control rule before the protected rule.

```yaml
security:
    firewalls:
        # Public endpoints (login, token creation) can be on the same firewall
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
            provider: app_user_provider
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
        $user = $this->getUser();
        $tokenOwner = $user->getTokenOwner();

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
    $token = substr($token, 7);
    
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

You can use the example below which demonstrates the JOINED inheritance strategy (InheritanceType JOINED), a discriminator column/map and implementing HasAccessTokensInterface on a common base class so different owner types (User, Client, etc.) share the same token mapping.

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
    protected ?int|string $id = null;

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

## What the package publishes and why

When the bundle is installed via Composer + Symfony Flex, the recipe publishes configuration files into your project to make integration straightforward:

- config/packages/sanctum.yaml — the main package configuration where you set key options (including owner_class);
- config/packages/doctrine.yaml — an optional example showing a `resolve_target_entities` entry referencing the package parameter `%sanctum.owner_class%`.

Why this is useful
- sanctum.yaml provides a simple and safe place to declare which class in your application will own tokens (owner_class). The bundle exposes this parameter to the container so other configs can reference it.
- Publishing doctrine.yaml provides a convenient example of how to configure Doctrine so that the `Jonston\SanctumBundle\Contract\HasAccessTokensInterface` resolves to your owner class. You can accept the published file as-is or copy/adjust it in your project.

How to configure owner_class
1. Open `config/packages/sanctum.yaml` (published by the recipe).

```yaml
sanctum:
    owner_class: App\Entity\User
    token_length: 40
    default_expiration_hours: 24
```

2. Set `owner_class` to the class that will own tokens (e.g. App\Entity\User or your TokenOwner base class).

3. If you prefer static Doctrine mapping, check the published `config/packages/doctrine.yaml`. It uses `%sanctum.owner_class%`:

```yaml
doctrine:
  orm:
    resolve_target_entities:
      Jonston\SanctumBundle\Contract\HasAccessTokensInterface: '%sanctum.owner_class%'
```

4. After editing configs run:

```bash
composer dump-autoload
php bin/console cache:clear
```

Notes and recommendations
- You may choose not to accept the published doctrine.yaml and configure mapping manually in your project if you have special Doctrine rules.
- The recipe only publishes example files — the bundle does not force their use and you can override or remove published configs.
- While it's possible to configure mapping programmatically (CompilerPass or listener), the recommended default is to use the published sanctum.yaml + doctrine.yaml for clarity and simplicity.

---

## Requirements

- PHP 8.1+
- Symfony 6.0+
- Doctrine ORM

## License

MIT License. See [LICENSE](LICENSE) for details.
