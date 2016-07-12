<?php

namespace Backend\UchetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Costs
 */
class Costs
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $catid;

    /**
     * @var text
     */
    private $sum;

    /**
     * @var int
     */
    private $date;

    /**
     * @var text
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
     * Set catid
     *
     * @param integer $catid
     * @return Costs
     */
    public function setCatid($catid)
    {
        $this->catid = $catid;

        return $this;
    }

    /**
     * Get catid
     *
     * @return integer 
     */
    public function getCatid()
    {
        return $this->catid;
    }

    /**
     * Set sum
     *
     * @param text $sum
     * @return Costs
     *
     */
    public function setSum($sum)
    {
        $this->sum = strval($sum);

        return $this;
    }

    /**
     * Get sum
     *
     * @return real
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set date
     *
     * @param integer $date
     * @return Costs
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return integer 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set name
     *
     * @param text $name
     * @return Costs
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return text
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return array
     */
    public function getCostsAll($get_doctrine)
    {
        $repository = $get_doctrine->getRepository('BackendUchetBundle:Costs');

        $query = $repository->createQueryBuilder('c')
            ->select('c.name cost_name, c.sum, c.date, u.name category_name')
           ->leftJoin('BackendUchetBundle:Categories', 'u', 'WITH', 'c.catid = u.id')
            ->orderBy('c.id', 'DESC')
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
    
    
}
