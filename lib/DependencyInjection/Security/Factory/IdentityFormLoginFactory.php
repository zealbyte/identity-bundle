<?php
namespace ZealByte\Bundle\IdentityBundle\DependencyInjection\Security\Factory
{
	use Symfony\Component\Config\Definition\Builder\NodeDefinition;
	use Symfony\Component\DependencyInjection\ChildDefinition;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\DependencyInjection\Reference;
	use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

	class IdentityFormLoginFactory extends FormLoginFactory
	{
		public function getKey ()
		{
			return 'identity_form_login';
		}

		protected function getListenerId ()
		{
			return 'ZealByte\Identity\Security\Firewall\IdentityAuthenticationListener';
		}

		protected function createAuthProvider (ContainerBuilder $container, $id, $config, $userProviderId)
		{
			$provider = 'security.authentication.provider.identity.'.$id;

			$container
				->setDefinition($provider, new ChildDefinition('ZealByte\Identity\Security\Authentication\Provider\IdentityAuthenticationProvider'))
				->replaceArgument(1, new Reference($userProviderId))
				->replaceArgument(3, $id);

			return $provider;
		}
	}
}
