<?php
namespace ZealByte\Bundle\IdentityBundle\Controller
{
	use InvalidArgumentException;
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\RedirectResponse;
	use Symfony\Component\Form\Extension\Core\Type\SubmitType;
	use ZealByte\Bundle\PlatformBundle\Controller\ContextControllerTrait;
	use ZealByte\Platform\Context\ModalContext;
	use ZealByte\Identity\ZealByteIdentity;
	use ZealByte\Identity\Form\Extension\Register\Type\RegisterFormType;
	use ZealByte\Identity\Component\RegisterFormComponent;
	use ZealByte\Identity\Component\ForgotPasswordFormComponent;
	use ZealByte\Identity\Entity\User;
	use ZealByte\Util;

	class RegistrationController extends Controller
	{
		use ContextControllerTrait;

		protected $isEmailConfirmationRequired = false;

		/**
		 * Register action.
		 *
		 * @param Request $request
		 * @return Response
		 */
		public function registerAction (Request $request)
		{
			$user = $this->get('identity.user_manager')->createNewUser();

			$form = ($this->get('form.factory')->createBuilder(RegisterFormType::class, $user, [
				'action' => $this->generateUrl(ZealByteIdentity::ROUTE_REGISTER),
				'method' => 'post',
				'name_options' => [
					'attr' => ['title' => 'Shown publicly'],
				],
			]))
			->add('register', SubmitType::class, [
				'label' => 'Register',
			])
			->getForm();

			try {
				$form->handleRequest($request);

				if ($form->isSubmitted() && $form->isValid()) {
					$user = $form->getData();

					$this->get('identity.user_manager')->validatePasswordStrength($user);
					$this->get('identity.user_manager')->insert($user);

					$this->get('messages')->addSuccess('Account created.', 'You are one lucky person to have an accunt!');

					if ($this->isEmailConfirmationRequired) {
						$user->setEnabled(false)->setConfirmationToken(Util\Random::entropicToken());
						//$app['pam.mailer']->sendConfirmationMessage($user);

						return $this->redirect($this->generateUrl('identity.registration.confirmation-sent', [
							'email' => $user->getEmail(),
						]));
					}

					return $this->redirect($this->generateUrl(ZealByteIdentity::ROUTE_LOGIN));
				}
			} catch (\Error $e) {
				$this->get('messages')->addError($e->getMessage());
			} catch (\Exception $e) {
				$this->get('messages')->addException($e->getMessage(), $e);
			}

			$registerFormComponent = (new RegisterFormComponent())
				->setForm($form->createView())
				->setUsernameRequired(true);

			return $this->createContext($request, $registerFormComponent, [
				'title' => 'register',
			]);
		}

		/**
		 * Action to show the user the email confirmation send message
		 *
		 * @param Request $request
		 * @param string $email
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 */
		public function confirmationSentAction (Request $request, string $email)
		{
			return $this->render('@Identity/register_confirmation_sent.html.twig', [
				'email' => $email(),
			]);
		}

		/**
		 * Action to handle email confirmation links.
		 *
		 * @param Request $request
		 * @param string $token
		 * @return \Symfony\Component\HttpFoundation\RedirectResponse
		 */
		public function confirmEmailAction (Request $request, $token)
		{
			$user = $this->get('identity.user_manager')->findOneBy(['confirmationToken' => $token]);

			if (!$user) {
				$this->get('session')->getFlashBag()->set('alert', 'Sorry, your email confirmation link has expired.');

				return $this->redirect($this->generateUrl(ZealByteIdentity::ROUTE_LOGIN));
			}

			$user->setConfirmationToken(null);
			$user->setEnabled(true);
			$this->get('identity.user_manager')->update($user);

			$this->get('identity.user_manager')->loginAsUser($user);

			$this->get('session')->getFlashBag()->set('alert', 'Thank you! Your account has been activated.');

			return $this->redirect($this->generateUrl('identity.user.manage.view', ['id' => $user->getId()]));
		}

		/**
		 * Action to resend an email confirmation message.
		 *
		 * @param Request $request
		 * @return mixed
		 */
		public function resendConfirmationAction (Request $request)
		{
			$email = $request->request->get('email');
			$user = $this->get('identity.user_manager')->findOneBy(array('email' => $email));

			if (!$user) {
				$this->get('session')->getFlashBag()->set('alert', 'There was an error sending confirmation email.');
				return $this->redirect($this->generateUrl('identity.status.user_disabled'));
			}

			$user->setConfirmationToken(Util\Random::entropicToken());
			$this->get('identity.user_manager')->update($user);

			//$app['pam.mailer']->sendConfirmationMessage($user);

			return $this->render('@Identity/register_confirmation_sent.html.twig', [
				'email' => $user->getEmail(),
			]);
		}

	}
}
