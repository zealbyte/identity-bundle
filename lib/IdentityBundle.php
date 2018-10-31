<?php
namespace ZealByte\Bundle\IdentityBundle
{
	use Symfony\Component\HttpKernel\Bundle\Bundle;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\DependencyInjection\Compiler\PassConfig;
	use ZealByte\Bundle\SecurityBundle\DependencyInjection\CompilerPass\UsersPass;
	use ZealByte\Bundle\IdentityBundle\DependencyInjection\CompilerPass\WorkflowsCompilerPass;
	use ZealByte\Bundle\IdentityBundle\DependencyInjection\Security\Factory\IdentityFormLoginFactory;

	class IdentityBundle extends Bundle
	{
    /**
     * Boots the Bundle.
     */
    public function boot ()
    {
    }

    /**
     * Shutdowns the Bundle.
     */
    public function shutdown ()
    {
    }

    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     */
		public function build (ContainerBuilder $container)
		{
			$container->addCompilerPass(new WorkflowsCompilerPass());

			$security = $container->getExtension('security');
			$security->addSecurityListenerFactory(new IdentityFormLoginFactory());
		}

	}
}
