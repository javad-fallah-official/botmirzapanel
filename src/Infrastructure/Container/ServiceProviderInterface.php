<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Container;

use BotMirzaPanel\Shared\Contracts\ContainerInterface;

interface ServiceProviderInterface
{
    public function register(ContainerInterface $container): void;
    public function boot(ContainerInterface $container): void;
    public function provides(): array;
    public function isDeferred(): bool;
}