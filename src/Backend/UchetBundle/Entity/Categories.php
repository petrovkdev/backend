<?php

namespace Backend\UchetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Categories
 */
class Categories
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Categories
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return array
     */
    public function getCategoryAll($get_doctrine,$order,$sort)
    {
        $repository = $get_doctrine->getRepository('BackendUchetBundle:Categories');

        $query = $repository->createQueryBuilder('c')
            ->orderBy('c.'.$order, $sort)
            ->getQuery();

        return $query->getResult();
    }

    /**
     *
     * @return boll
     */
    public function save($get_doctrine, $obj)
    {
        $em = $get_doctrine->getManager();
        $em->persist($obj);
        $em->flush();
        
        return false;
    }

    /**
     *
     * @return array 
     */
    public function getCategoryChoices($get_doctrine)
    {
        $cat_all = self::getCategoryAll($get_doctrine,'name','ASC');
        $choices = [];
        
        foreach ($cat_all as $k => $v){
            $choices[$v->name] = $v->id;
        }
        
        return $choices;
    }
}
