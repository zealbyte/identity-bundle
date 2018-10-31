<?php
namespace ZealByte\Bundle\IdentityBundle\DependencyInjection
{
	use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
	use Symfony\Component\Config\Definition\Builder\TreeBuilder;
	use Symfony\Component\Config\Definition\ConfigurationInterface;

	class Configuration implements ConfigurationInterface
	{
		public function getConfigTreeBuilder ()
		{
			$treeBuilder = new TreeBuilder();
			$rootNode = $treeBuilder->root('identity');

			$rootNode
				->children()
					->scalarNode('login_template')->defaultValue('@Identity/login.html.twig')->end()
					->end();

			$this->addUserProviderSection($rootNode);

			return $treeBuilder;
		}

		private function addUserProviderSection (ArrayNodeDefinition $rootNode) : void
		{
			$rootNode
				->children()
					->arrayNode('user_provider')
					->addDefaultsIfNotSet()
					->children()
						->integerNode('refresh_threshold')->defaultValue(10)->end()
						->scalarNode('refresh_threshold_key')->defaultValue('identity.last_refresh_check')->end()
					->end()
				->end();
		}

	}
}
