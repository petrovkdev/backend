<?php

namespace Backend\UchetBundle\Twig\Extension;


class ArraySum extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('sum', 'array_sum'),
        );
    }
 
    public function getName()
    {
        return 'array_sum';
    }
}