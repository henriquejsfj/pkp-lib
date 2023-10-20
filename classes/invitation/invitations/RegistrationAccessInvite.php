<?php

/**
 * @file invitation/invitations/RegistrationAccessInvite.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RegistrationAccessInvite
 *
 * @ingroup invitations
 *
 * @brief Registration with Access Key invitation
 */

namespace PKP\invitation\invitations;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use Illuminate\Mail\Mailable;
use PKP\config\Config;
use PKP\core\Core;
use PKP\core\PKPApplication;
use PKP\invitation\invitations\enums\InvitationStatus;
use PKP\user\User;

class RegistrationAccessInvite extends BaseInvitation
{
    /**
     * Create a new invitation instance.
     */
    public function __construct(
        public ?int $invitedUserId,
        ?int $contextId = null
    ) {
        $expiryDays = Config::getVar('email', 'validation_timeout');

        parent::__construct($invitedUserId, null, $contextId, null, $expiryDays);
    }

    public function getMailable(): ?Mailable
    {
        if (isset($this->mailable)) {
            $url = $this->getAcceptUrl();

            $this->mailable->buildViewDataUsing(function () use ($url) {
                return [
                    'activateUrl' => $url
                ];
            });
        }

        return $this->mailable;
    }

    /**
     */
    public function preDispatchActions(): bool
    {
        $invitations = Repo::invitation()
            ->filterByStatus(InvitationStatus::PENDING)
            ->filterByClassName($this->className)
            ->filterByContextId($this->contextId)
            ->filterByUserId($this->invitedUserId)
            ->getMany();

        foreach ($invitations as $invitation) {
            $invitation->markStatus(InvitationStatus::CANCELLED);
        }

        return true;
    }

    public function acceptHandle(): void
    {
        $user = Repo::user()->get($this->invitedUserId, true);

        if (!$user) {
            return;
        }

        $request = Application::get()->getRequest();
        $validated = $this->_validateAccessKey($user, $request);

        if ($validated) {
            parent::acceptHandle();
        }

        $url = PKPApplication::get()->getDispatcher()->url(
            PKPApplication::get()->getRequest(),
            PKPApplication::ROUTE_PAGE,
            null,
            'user',
            'activateUser',
            [
                $user->getUsername(),
            ]
        );

        if (isset($this->contextId)) {
            $contextDao = Application::getContextDAO();
            $this->context = $contextDao->getById($this->contextId);

            $url = PKPApplication::get()->getDispatcher()->url(
                PKPApplication::get()->getRequest(),
                PKPApplication::ROUTE_PAGE,
                $this->context->getData('urlPath'),
                'user',
                'activateUser',
                [
                    $user->getUsername(),
                ]
            );
        }

        $request->redirectUrl($url);
    }

    private function _validateAccessKey(User $user, Request $request): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->getDateValidated() === null) {
            // Activate user
            $user->setDisabled(false);
            $user->setDisabledReason('');
            $user->setDateValidated(Core::getCurrentDate());
            Repo::user()->edit($user);

            return true;
        }

        return false;
    }
}
