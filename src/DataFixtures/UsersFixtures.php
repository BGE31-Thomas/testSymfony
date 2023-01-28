<?php

namespace App\DataFixtures;

use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;
use Symfony\Component\String\Slugger\SluggerInterface as SluggerSluggerInterface;

class UsersFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordEncoder, private SluggerSluggerInterface $slugger){}
    
    public function load(ObjectManager $manager): void
    {
        $admin = new Users();
        $admin->setEmail("tpjarles@gmail.com");
        $admin->setLastName('Thom');
        $admin->setFirstName("Thom");
        $admin->setAddress("1 rue de la rue");
        $admin->setZipcode("31000");
        $admin->setCity("Toulouse");
        $admin->setPassword($this->passwordEncoder->hashPassword($admin,"totoro"));
        $admin->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);

        $faker = Faker\Factory::create('fr_FR');
        for ($i=1;$i<= 5;$i++){
            $user = new Users();
            $user->setEmail($faker->email);
            $user->setLastName($faker->lastName);
            $user->setFirstName($faker->firstName);
            $user->setAddress($faker->streetAddress);
            $user->setZipcode(str_replace(" ","",$faker->postcode));
            $user->setCity($faker->city);
            $user->setPassword($this->passwordEncoder->hashPassword($user,"qsqsqs"));

            $manager->persist($user);

        }

        $manager->flush();
    }
}
