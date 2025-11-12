<?php
namespace Nevinny\AdminCoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Если пользователь уже залогинен, редирект в админку
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        // Получаем ошибку логина, если есть
        $error = $authenticationUtils->getLastAuthenticationError();

        // Последний введенный email
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@AdminCore/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
        // Этот метод может быть пустым - он будет перехвачен фаерволом
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
