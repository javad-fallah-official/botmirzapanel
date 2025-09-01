<?php

namespace BotMirzaPanel\Infrastructure\External\Panel;

/**
 * Panel Adapter Factory Interface
 * Creates panel adapters based on panel type
 */
interface PanelAdapterFactoryInterface
{
    /**
     * Create a panel adapter instance
     *
     * @param string $panelType The type of panel (marzban, mikrotik, xui, etc.)
     * @param array $config Panel configuration
     * @return PanelAdapterInterface
     * @throws \InvalidArgumentException If panel type is not supported
     */
    public function create(string $panelType, array $config): PanelAdapterInterface;

    /**
     * Get list of supported panel types
     *
     * @return array
     */
    public function getSupportedTypes(): array;

    /**
     * Check if a panel type is supported
     *
     * @param string $panelType
     * @return bool
     */
    public function supports(string $panelType): bool;
}