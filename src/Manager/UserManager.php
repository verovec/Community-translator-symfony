<?php

namespace App\Manager;

use App\Entity\User;

class UserManager
{

    private $em;

    public function __construct($entityManager)
    {
        $this->em = $entityManager;
    }

    public function save_user(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function update_user(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}