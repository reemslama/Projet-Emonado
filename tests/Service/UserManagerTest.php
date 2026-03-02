<?php
namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser(): void
    {
        $user = (new User())
            ->setEmail('user@test.com')
            ->setNom('Doe')
            ->setPassword('secret123');

        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = (new User())->setNom('Doe')->setPassword('secret123');
        (new UserManager())->validate($user);
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = (new User())
            ->setEmail('not-an-email')
            ->setNom('Doe')
            ->setPassword('secret123');
        (new UserManager())->validate($user);
    }

    public function testUserWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = (new User())
            ->setEmail('user@test.com')
            ->setPassword('secret123');
        (new UserManager())->validate($user);
    }

    public function testUserWithoutPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = (new User())
            ->setEmail('user@test.com')
            ->setNom('Doe');
        (new UserManager())->validate($user);
    }
}