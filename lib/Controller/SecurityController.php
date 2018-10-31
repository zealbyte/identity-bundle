<?php
namespace ZealByte\Bundle\IdentityBundle\Controller
{
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\HttpFoundation\RedirectResponse;
	use Symfony\Component\Translation\TranslatorInterface;
	use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
	use Symfony\Component\Form\FormError;
	use ZealByte\Bundle\PlatformBundle\Controller\ContextControllerTrait;
	use ZealByte\Platform\Context\ContextInterface;
	use ZealByte\Identity\Component\LoginFormComponent;
	use ZealByte\Identity\Component\RecoverComponent;
	use ZealByte\Identity\Component\UserDisabledMessageComponent;
	use ZealByte\Identity\ZealByteIdentity;

	/**
	 * Controller to handle auth user management
	 *
	 * @package
	 */
	class SecurityController extends Controller
	{
		use ContextControllerTrait;

		/**
		 * Login action.
		 * TODO We need to determine if this is a new session or expired session
		 *
		 * @param Symfony\Component\HttpFoundation\Request
		 * @return ZealByte\Platform\Context\ModalContext
		 */
		public function loginAction (Request $request, TranslatorInterface $translator)
		{
			$title = $translator->trans('auth.login');
			$loginFormComponent = new LoginFormComponent($request);

			return $this->createContext($request, $loginFormComponent, [
				'title' => $title,
			]);
		}

		/**
		 */
		public function logoutAction (Request $request) : RedirectResponse
		{
			$path = $this->generateUrl(ZealByteIdentity::ROUTE_FORM_LOGIN);

			return new RedirectResponse($path, Response::HTTP_FOUND);
		}

		/**
		 * @param Request $request
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
		 */
		public function RecoverAction (Request $request)
		{
			//if (!$this->isRecoverEnabled())
			//	throw new NotFoundHttpException("The page you are looking for does not exist!");

			$recoverComponent = new RecoverComponent($request);

			return $this->createContext($request, $recoverComponent);
		}

		/**
		 * Action to show the user the user disabled message.
		 *
		 * @param Request $request
		 * @param string $token
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
		 */
		public function userDisabledAction (Request $request) : ContextInterface
		{
			//$user = $this->userManager->refreshUser($authException->getUser());

			$userDisabledMessageComponent = (new UserDisabledMessageComponent())
				->setEmail(null)
				->setFromAddress('whatever@whatever.com')
				->setResendUrl($this->generateUrl(ZealByteIdentity::ROUTE_REGISTER_CONFIRM_RESEND));

			return $this->createContext($request, $userDisabledMessageComponent);
		}

		/**
		 * @param Request $request
		 * @param string $token
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
		 */
		public function resetPasswordAction (Request $request, $token)
		{
			if (!$this->isPasswordResetEnabled())
				throw new NotFoundHttpException();

			$tokenExpired = false;

			$user = $this->get('identity.user_manager')->findOneBy(['confirmationToken' => $token]);

			if (!$user)
				$tokenExpired = true;
			else if ($user->isPasswordResetRequestExpired(86400))
				$tokenExpired = true;

			if ($tokenExpired) {
				$this->get('session')->getFlashBag()->set('alert', 'Sorry, your password reset link has expired.');
				return $this->redirect($this->generateUrl(ZealByteIdentity::ROUTE_FORM_LOGIN));
			}

			$error = '';
			if ($request->isMethod('POST')) {
				// Validate the password
				$password = $request->request->get('password');
				if ($password != $request->request->get('confirm_password')) {
					$error = 'Passwords don\'t match.';
				} else if ($error = $this->get('identity.user_manager')->validatePasswordStrength($user, $password)) {
				} else {
					// Set the password
					$this->get('identity.user_manager')->setUserPassword($user, $password);
					$user->setConfirmationToken(null);
					$user->setEnabled(true);
					$this->get('identity.user_manager')->update($user);

					$this->get('session')->getFlashBag()->set('alert', 'Your password has been reset.');

					return $this->redirect($this->generateUrl(ZealByteIdentity::ROUTE_FORM_LOGIN));
				}
			}

			return $this->render('@Identity/recovery/reset_password.html.twig', [
				'user' => $user,
				'token' => $token,
				'error' => $error,
			]);
		}

	}
}
