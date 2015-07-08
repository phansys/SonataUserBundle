<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Sonata\UserBundle\Form\Handler;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This file is an adapted version of FOS User Bundle RegistrationFormHandler class (1.x).
 *
 *    (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 */
class RegistrationFormHandler implements EventSubscriberInterface
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var UserManagerInterface
     */
    protected $userManager;
    /**
     * @var FormInterface
     */
    protected $form;
    /**
     * @var MailerInterface
     */
    protected $mailer;
    /**
     * @var TokenGeneratorInterface
     */
    protected $tokenGenerator;

    /**
     * @param FormInterface           $form
     * @param Request                 $request
     * @param UserManagerInterface    $userManager
     * @param MailerInterface         $mailer
     * @param TokenGeneratorInterface $tokenGenerator
     */
    public function __construct(FormInterface $form, Request $request, UserManagerInterface $userManager, MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator)
    {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_INITIALIZE => 'onRegistrationInitialize',
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        );
    }

    /**
     * @param FormEvent $event
     *
     * @return bool
     */
    public function onRegistrationInitialize(FormEvent $event)
    {
        $user = $this->createUser();
        $this->form->setData($user);
        $request = $event->getRequest();
        // @todo: find a way to retrieve this flag
        $confirmation = false;

        if ($request->isMethod('POST')) {
            $this->form->handleRequest($request);

            if ($this->form->isValid()) {
                $this->onSuccess($user, $confirmation);

                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $confirmation
     */
    protected function onSuccess(UserInterface $user, $confirmation)
    {
        if ($confirmation) {
            $user->setEnabled(false);
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }

            $this->mailer->sendConfirmationEmailMessage($user);
        } else {
            $user->setEnabled(true);
        }

        $this->userManager->updateUser($user);
    }

    /**
     * @return UserInterface
     */
    protected function createUser()
    {
        return $this->userManager->createUser();
    }
}
