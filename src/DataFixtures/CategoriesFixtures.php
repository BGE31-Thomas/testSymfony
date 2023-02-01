<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoriesFixtures extends Fixture
{
    private $counter = 1;
    
    public function __construct(private SluggerInterface $slugger){

    }
    
    public function load(ObjectManager $manager): void
    {
        $parent = $this->createCategory('Informatique',null,$manager);

        $category = $this->createCategory(name:'Ordinateurs portables',parent:$parent,manager:$manager);
    
        $manager->flush();
    }

    public function createCategory(string $name, Categories $parent = null,ObjectManager $manager):Categories
    {
        $category = new Categories();
        $category->setName($name);
        $category->setSlug($this->slugger->slug($category->getName())->lower());
        $category->setParent($parent);
        $category->setCategoryOrder(0);
        $manager->persist($category);

        $this->addReference('cat-'.$this->counter,$category);
        $this->counter++;

        return $category;
        
    }
}
