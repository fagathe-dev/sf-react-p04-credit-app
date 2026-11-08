<?php

declare(strict_types=1);

namespace Credit\Core;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Enregistre les services du domaine (catalogue, scoring) et la configuration `credit_core`.
 *
 *     # config/packages/credit_core.yaml de l'application hôte
 *     credit_core:
 *         asset_base_uri: '%env(DEFAULT_URI)%'
 */
final class CreditCoreBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('data_dir')
                    ->info('Dossier des fichiers JSON des partenaires (un fichier par partenaire).')
                    ->defaultValue(\dirname(__DIR__) . '/config/data')
                ->end()
                ->scalarNode('asset_base_uri')
                    ->info('URL publique de l\'application qui sert /assets/logos/ : préfixe l\'URL absolue des logos (partnerLogo). Null : chemin relatif.')
                    ->defaultNull()
                ->end()
            ->end();
    }

    /**
     * @param array{data_dir: string, asset_base_uri: ?string} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()
            ->set('credit_core.data_dir', $config['data_dir'])
            ->set('credit_core.asset_base_uri', $config['asset_base_uri'])
            ->set('credit_core.logos_dir', \dirname(__DIR__) . '/assets/logos');

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Credit\\Core\\', __DIR__ . '/')
            ->exclude([
                __DIR__ . '/DTO',
                __DIR__ . '/Catalog/DTO',
                __DIR__ . '/Catalog/Exception',
                __DIR__ . '/Enum',
                __DIR__ . '/Scoring/Model',
                __DIR__ . '/Util',
                __DIR__ . '/CreditCoreBundle.php',
            ]);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
