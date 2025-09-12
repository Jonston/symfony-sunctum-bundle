# Changelog

Все изменения в проекте jonston/symfony-sanctum-bundle будут документированы в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
и проект придерживается [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Добавлено
- Начальная реализация Symfony Sanctum Bundle
- Сущность AccessToken с поддержкой хэширования токенов
- Динамическая настройка связей Doctrine через MetadataListener
- TokenService для управления жизненным циклом токенов
- TokenableInterface и TokenableTrait для простой интеграции
- TokenAuthenticator для Symfony Security
- Команда sanctum:prune-expired для очистки просроченных токенов
- Поддержка множественных владельцев токенов
- Конфигурируемая длина токенов и время жизни
- Полное покрытие тестами (Unit и Integration)
- Подробная документация с примерами

### Функциональность
- Создание и управление токенами доступа
- Автоматическое хэширование токенов перед сохранением
- Поддержка токенов с ограниченным сроком действия
- Отслеживание времени последнего использования
- Отзыв отдельных токенов и всех токенов пользователя
- Автоматическая очистка просроченных токенов
- Интеграция с Symfony Security Component

### Требования
- PHP 8.1+
- Symfony 6.0+
- Doctrine ORM
