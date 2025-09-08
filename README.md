# Symfony Sanctum Bundle

Symfony-бандл, который предоставляет аутентификацию через персональные токены доступа в стиле Laravel Sanctum.

## Описание

Этот бандл позволяет легко интегрировать токен-based аутентификацию в Symfony приложения. Он предоставляет функциональность, аналогичную Laravel Sanctum, для создания и управления персональными токенами доступа.

## Возможности

- ✅ Создание персональных токенов доступа
- ✅ Токен-based аутентификация через Bearer токены
- ✅ Управление жизненным циклом токенов
- ✅ Отслеживание времени последнего использования
- ✅ Поддержка истечения токенов
- ✅ Интеграция с Symfony Security компонентом
- ✅ Поддержка любых сущностей через интерфейс TokenableInterface

## Требования

- PHP 8.2 или выше
- Symfony 6.0+ или 7.0+
- Doctrine ORM 2.14+ или 3.0+
- Doctrine Bundle 2.8+

## Установка

Установите бандл через Composer:

```bash
composer require jonston/symfony-sanctum-bundle
```

Добавьте бандл в `config/bundles.php`:

```php
<?php

return [
    // ... другие бандлы
    Jonston\SanctumBundle\SanctumBundle::class => ['all' => true],
];
```

Создайте и выполните миграцию для таблицы токенов:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Настройка

### 1. Настройка Security

Добавьте аутентификатор в `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - Jonston\SanctumBundle\Security\TokenAuthenticator
```

### 2. Настройка пользовательской сущности

Реализуйте интерфейс `TokenableInterface` в вашей пользовательской сущности:

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

    // ... остальные поля и методы

    public function getId(): ?int
    {
        return $this->id;
    }

    // Методы интерфейса TokenableInterface уже реализованы в TokenableTrait
}
```

## Использование

### Создание токенов

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
        $user = $this->getUser(); // Получаем аутентифицированного пользователя

        $token = $this->tokenService->createToken($user, 'API Token');

        return new JsonResponse([
            'token' => $token->getPlainTextToken(),
            'name' => $token->getName(),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }
}
```

### Использование токенов в запросах

Отправляйте токен в заголовке Authorization:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Content-Type: application/json" \
     http://your-app.com/api/protected-endpoint
```

### Получение аутентифицированного пользователя

В контроллерах API:

```php
#[Route('/api/profile', methods: ['GET'])]
public function profile(): JsonResponse
{
    /** @var \Jonston\SanctumBundle\Security\UserAdapter $userAdapter */
    $userAdapter = $this->getUser();
    
    $tokenable = $userAdapter->getTokenable(); // Ваша пользовательская сущность
    
    return new JsonResponse([
        'id' => $tokenable->getTokenableId(),
        'type' => $tokenable->getTokenableType()
    ]);
}
```

### Управление токенами

```php
// Получение всех токенов пользователя
$tokens = $this->tokenService->getTokensFor($user);

// Отзыв токена
$this->tokenService->revokeToken($token);

// Обновление времени последнего использования
$this->tokenService->updateLastUsed($user);
```

## Архитектура

### Основные компоненты

1. **PersonalAccessToken** - сущность для хранения токенов
2. **TokenService** - основной сервис для работы с токенами
3. **TokenHasher** - сервис для хеширования и генерации токенов
4. **TokenAuthenticator** - аутентификатор Symfony Security
5. **UserAdapter** - адаптер для интеграции с Symfony Security
6. **TokenableInterface** - интерфейс для сущностей, которые могут иметь токены
7. **TokenableTrait** - трейт с базовой реализацией интерфейса

### База данных

Структура таблицы `personal_access_tokens`:

| Поле | Тип | Описание |
|------|-----|----------|
| id | integer | Первичный ключ |
| name | string(255) | Название токена |
| token | string(64) | Хешированный токен (индекс) |
| tokenable_type | string(255) | Тип сущности |
| tokenable_id | string(255) | ID сущности |
| created_at | datetime_immutable | Время создания |
| expires_at | datetime_immutable | Время истечения (nullable) |
| last_used_at | datetime_immutable | Время последнего использования (nullable) |

## Безопасность

- Токены хешируются с помощью SHA-256
- Поддерживается проверка времени истечения
- Отслеживается время последнего использования
- Используется cryptographically secure random generation для токенов

## Тестирование

Запустите тесты:

```bash
vendor/bin/phpunit
```

## Расширение функциональности

### Кастомный TokenHasher

```php
<?php

namespace App\Service;

use Jonston\SanctumBundle\Service\TokenHasher;

class CustomTokenHasher extends TokenHasher
{
    public function generatePlainToken(): string
    {
        // Ваша логика генерации токенов
        return parent::generatePlainToken();
    }

    public function hashToken(string $plainToken): string
    {
        // Ваша логика хеширования
        return parent::hashToken($plainToken);
    }
}
```

### Кастомная логика аутентификации

Вы можете расширить `TokenAuthenticator` для добавления дополнительной логики:

```php
<?php

namespace App\Security;

use Jonston\SanctumBundle\Security\TokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;

class CustomTokenAuthenticator extends TokenAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // Дополнительная логика проверки
        return parent::supports($request);
    }
}
```

## Лицензия

MIT License

## Автор

Eugene (eugene@example.com)

## Поддержка

Если у вас есть вопросы или предложения, создайте issue в репозитории проекта.
