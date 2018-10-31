<?php
namespace ZealByte\Bundle\IdentityBundle\DependencyInjection
{
	use ReflectionClass;
	use RuntimeException;
	use Symfony\Component\Config\FileLocator;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\DependencyInjection\Loader;
	use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
	use Symfony\Component\HttpKernel\DependencyInjection\Extension;
	use Symfony\Component\HttpFoundation\RequestMatcher;
	use ZealByte\Identity\Security\User\IdentityUserProvider;
	use ZealByte\Identity\User\IdentityUserInterface;
	use ZealByte\Identity\Workflow\Subject\RsvpTrialSubjectInterface;
	use ZealByte\Identity\ZealByteIdentity;

	class IdentityExtension extends Extension implements PrependExtensionInterface
	{
		/**
		 * {@inheritdoc}
		 */
		public function load (array $configs, ContainerBuilder $container)
		{
			$loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
			$loader->load('services.xml');

			$configuration = new Configuration();
			//$configuration = $this->getConfiguration($configs, $container);
			$config = $this->processConfiguration($configuration, $configs);

			$userProviderConfig = $config['user_provider'];

			$container->setParameter('identity.config.user_provider', [
				'threshold' => $userProviderConfig['refresh_threshold'],
				'threshold_key' => $userProviderConfig['refresh_threshold_key'],
			]);
		}

		/**
		 *
		 */
		public function prepend (ContainerBuilder $container)
		{
			$identityPath = rtrim(dirname((new ReflectionClass(ZealByteIdentity::class))->getFileName()), DIRECTORY_SEPARATOR);

			$this->prependSecurity($container, $identityPath);
			$this->prependDoctrine($container, $identityPath);
			$this->prependTwig($container, $identityPath);
			$this->prependTranslator($container, $identityPath);
			$this->prependWorkflow($container);
		}

		/**
		 *
		 */
		private function prependSecurity (ContainerBuilder $container, string $identity_path) : void
		{
			if (!$container->hasExtension('security'))
				throw new RuntimeException('The zealbyte platform identity requires the security extension.');

			$config = [
				'providers' => [
					'identity' => [
						'id' => IdentityUserProvider::class,
					],
				],
				'encoders' => [
					IdentityUserInterface::class => 'bcrypt',
				],
			];

			$container->prependExtensionConfig('security', $config);
		}

		/**
		 *
		 */
		private function prependDoctrine (ContainerBuilder $container, string $identity_path) : void
		{
			if (!$container->hasExtension('doctrine'))
				return;

			$config = [
				'orm' => [
					'mappings' => [
						'identity' => [
							'alias' => 'Identity',
							'is_bundle' => false,
							'type' => 'xml',
							'dir' => "$identity_path/Resources/config/doctrine",
							'prefix' => 'ZealByte\\Identity\\Entity',
						],
					],
				],
			];

			$container->prependExtensionConfig('doctrine', $config);
		}

		/**
		 *
		 */
		private function prependTwig (ContainerBuilder $container, string $identity_path) : void
		{
			if (!$container->hasExtension('twig'))
				throw new RuntimeException('The zealbyte platform requires the twig extension.');

			$config = [
				'paths' => [
					"$identity_path/Resources/views" => 'Identity',
				],
			];

			$container->prependExtensionConfig('twig', $config);
		}

		/**
		 *
		 */
		private function prependTranslator (ContainerBuilder $container, string $identity_path) : void
		{
			if (!$container->hasExtension('framework'))
				throw new RuntimeException('Identity requires the framework extension.');

			$config = [
				'translator' => [
					'paths' => [
						"$identity_path/Resources/translations",
					],
				],
			];

			$container->prependExtensionConfig('framework', $config);
		}

		/**
		 *
		 */
		private function prependWorkflow (ContainerBuilder $container) : void
		{
			if (!$container->hasExtension('framework'))
				throw new RuntimeException('Identity requires the framework extension.');

			$config = [
				'workflows' => [
					'rsvp_trial' => [
						'type' => 'workflow',
						'audit_trail' => [
							'enabled' => true,
						],
						'marking_store' => [
							'type' => 'single_state',
							'argument' => 'status',
						],
						'supports' => [
							RsvpTrialSubjectInterface::class,
						],
						'initial_place' => RsvpTrialSubjectInterface::STATUS_INITIALIZED,
						'places' => [
							RsvpTrialSubjectInterface::STATUS_INITIALIZED,
							RsvpTrialSubjectInterface::STATUS_RSVP_PENDING,
							RsvpTrialSubjectInterface::STATUS_RSVP_SENT,
							RsvpTrialSubjectInterface::STATUS_RSVP_RECEIVED,
							RsvpTrialSubjectInterface::STATUS_TRIAL_PROCEEDING,
							RsvpTrialSubjectInterface::STATUS_TRIAL_PASSED,
							RsvpTrialSubjectInterface::STATUS_TRIAL_FAILED,
							RsvpTrialSubjectInterface::STATUS_VERIFIED,
							RsvpTrialSubjectInterface::STATUS_COMPLETED,
						],
						'transitions' => [
							'prep_rsvp' => [
								'from' => RsvpTrialSubjectInterface::STATUS_INITIALIZED,
								'to' => RsvpTrialSubjectInterface::STATUS_RSVP_PENDING,
							],
							'send_rsvp' => [
								'from' => RsvpTrialSubjectInterface::STATUS_RSVP_PENDING,
								'to' => RsvpTrialSubjectInterface::STATUS_RSVP_SENT,
							],
							'read_rsvp' => [
								'from' => RsvpTrialSubjectInterface::STATUS_RSVP_SENT,
								'to' => RsvpTrialSubjectInterface::STATUS_RSVP_RECEIVED,
							],
							'prep_trial' => [
								'from' => RsvpTrialSubjectInterface::STATUS_RSVP_RECEIVED,
								'to' => RsvpTrialSubjectInterface::STATUS_TRIAL_PROCEEDING,
							],
							'pass_trial' => [
								'from' => RsvpTrialSubjectInterface::STATUS_TRIAL_PROCEEDING,
								'to' => RsvpTrialSubjectInterface::STATUS_TRIAL_PASSED,
							],
							'fail_trial' => [
								'from' => RsvpTrialSubjectInterface::STATUS_TRIAL_PROCEEDING,
								'to' => RsvpTrialSubjectInterface::STATUS_TRIAL_FAILED,
							],
							'verify' => [
								'from' => RsvpTrialSubjectInterface::STATUS_TRIAL_PASSED,
								'to' => RsvpTrialSubjectInterface::STATUS_VERIFIED,
							],
							'complete' => [
								'from' => RsvpTrialSubjectInterface::STATUS_VERIFIED,
								'to' => RsvpTrialSubjectInterface::STATUS_COMPLETED,
							],
						],
					],
				],
			];

			$container->prependExtensionConfig('framework', $config);
		}

	}
}
