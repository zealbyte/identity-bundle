<?php
namespace ZealByte\Bundle\IdentityBundle\Controller
{
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Translation\TranslatorInterface;
	use ZealByte\Bundle\PlatformBundle\Controller\ContextControllerTrait;
	use ZealByte\Platform\Component\ContainerComponent;
	use ZealByte\Platform\Component\ControllerComponent;
	use ZealByte\Platform\Context\Context;
	use ZealByte\Identity\ZealByteIdentity;

	class AccountController extends Controller
	{
		use ContextControllerTrait;

		public function viewAccountAction (Request $request, ?string $section = 'index')
		{
			$selfComponent = new ControllerComponent(ZealByteIdentity::ROUTE_SELF);

			$container = (new ContainerComponent())
				->setView('@Identity/components.html.twig')
				->setBlock('account_container')
				->addComponent($selfComponent);

			return $this->createContext($request, $container, [
				'title' => 'myaccount',
			]);
		}

	}
}
