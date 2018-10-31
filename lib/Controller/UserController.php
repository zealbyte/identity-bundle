<?php
namespace ZealByte\Bundle\IdentityBundle\Controller
{
	use InvalidArgumentException;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpFoundation\RedirectResponse;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
	use Symfony\Component\Security\Core\Exception\AccessDeniedException;
	use Symfony\Component\Security\Core\Exception\DisabledException;
	use Symfony\Component\Security\Core\User\UserInterface;
	use ZealByte\Bundle\PlatformBundle\Controller\ContextControllerTrait;
	use ZealByte\Platform\Context\Context;
	use ZealByte\Platform\Component\Component;

	/**
	 * Controller to handle auth user management
	 *
	 * @package
	 */
	class UserController extends Controller
	{
		use ContextControllerTrait;

		/**
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 */
		public function viewSelfAction (Request $request, UserInterface $user)
		{
			$component = (new Component())
				->setView('@Identity/components.html.twig')
				->setBlock('user_all')
				->addParameter('user', $user);

			return $this->createContext($request, $component);
		}

		/**
		 * View user action.
		 *
		 * @param Request $request
		 * @param UserInterface $user
		 * @param string $id
		 * @return Response
		 * @throws NotFoundHttpException if no user is found with that ID.
		 */
		public function viewAction (Request $request, UserInterface $user, string $id)
		{
			if ((string) $id != (string) $user->getId())
				$this->denyAccessUnlessGranted('ROLE_MANAGE_USERS');

			if (!($who = $this->get('identity.user_manager')->findOneBy(['id' => $id])))
				throw new NotFoundHttpException("User not found!");

			$component = (new Component())
				->setView('@Identity/components.html.twig')
				->setBlock('user_all')
				->addParameter('user', $who);

			return $this->createContext($request, $component);
		}

		/**
		 * Edit user action.
		 *
		 * @param Application $app
		 * @param Request $request
		 * @param int $id
		 * @return Response
		 * @throws NotFoundHttpException if no user is found with that ID.
		 */
		public function editAction (Request $request, $id)
		{
			$errors = [];
			$user = $this->userManager->getUser($id);

			if (!$user)
				throw new NotFoundHttpException('No user was found with that ID.');

			if ($request->isMethod('POST')) {
				$user->setName($request->request->get('name'));
				$user->setEmail($request->request->get('email'));

				if ($request->request->has('username'))
					$user->setUsername($request->request->get('username'));

				if ($request->request->get('password')) {
					if ($request->request->get('password') != $request->request->get('confirm_password')) {
						$errors['password'] = 'Passwords don\'t match.';
					} else if ($error = $this->userManager->validatePasswordStrength($user, $request->request->get('password'))) {
						$errors['password'] = $error;
					} else {
						$this->userManager->setUserPassword($user, $request->request->get('password'));
					}
				}

				if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN') && $request->request->has('roles'))
					$user->setRoles($request->request->get('roles'));

				$errors += $this->userManager->validate($user);

				if (empty($errors)) {
					$this->userManager->update($user);
					$msg = 'Saved account information.' . ($request->request->get('password') ? ' Changed password.' : '');
					$app['session']->getFlashBag()->set('alert', $msg);
				}
			}

			return $app['twig']->render($this->getTemplate('edit'), [
				'layout_template' => $this->getTemplate('layout'),
				'error' => implode("\n", $errors),
				'user' => $user,
				'available_roles' => ['ROLE_USER', 'ROLE_ADMIN'],
				'image_url' => $this->getGravatarUrl($user->getEmail()),
				'isUsernameRequired' => true,
			]);
		}

		public function listAction (Request $request)
		{
			$order_by = $request->get('order_by') ?: 'name';
			$order_dir = $request->get('order_dir') == 'DESC' ? 'DESC' : 'ASC';
			$limit = (int)($request->get('limit') ?: 50);
			$page = (int)($request->get('page') ?: 1);
			$offset = ($page - 1) * $limit;

			$criteria = [];

			if (!$app['security.authorization_checker']->isGranted('ROLE_ADMIN'))
				$criteria['isEnabled'] = true;

			$users = $this->userManager->findBy($criteria, [
				'limit' => [$offset, $limit],
				'order_by' => [$order_by, $order_dir],
			]);

			$numResults = $this->userManager->findCount($criteria);

			foreach ($users as $user)
				$user->imageUrl = $this->getGravatarUrl($user->getEmail(), 40);

			return $app['twig']->render($this->getTemplate('list'), [
				'layout_template' => $this->getTemplate('layout'),
				'users' => $users,
			]);
		}

		/**
		 * @param string $email
		 * @param int $size
		 * @return string
		 */
		protected function getGravatarUrl ($email, $size = 80)
		{
			return '//www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?s=' . $size . '&d=identicon';
		}

	}
}
