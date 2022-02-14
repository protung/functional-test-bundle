<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Security;

use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Custom authenticated security token to be used in testing.
 * After upgrade to support Symfony 6.x it can be removed and TestBrowserToken can be used directly.
 */
final class TestToken extends TestBrowserToken
{
    public static function create(UserInterface $user): self
    {
        $self = new self($user->getRoles(), $user);

        $self->setAuthenticated(true, false);

        return $self;
    }
}
