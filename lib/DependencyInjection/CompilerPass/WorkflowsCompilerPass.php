<?php
namespace ZealByte\Bundle\IdentityBundle\DependencyInjection\CompilerPass
{
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
	use Symfony\Component\DependencyInjection\Definition;
	use Symfony\Component\DependencyInjection\Reference;
	use Symfony\Component\Workflow\Registry;

	class WorkflowsCompilerPass implements CompilerPassInterface
	{
		/**
		 * {@inheritdoc}
		 */
		public function process (ContainerBuilder $container)
		{
			if (!$container->hasDefinition(Registry::class))
				return;
		}

	}
}
