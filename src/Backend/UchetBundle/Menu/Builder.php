<?php


namespace Backend\UchetBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;


    public function mainMenu(FactoryInterface $factory, array $options)
    {

        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);

        $menu = $factory->createItem('root');
        $menu->setChildrenAttributes(['class'=>'nav']);
        if(isset($cfg->limit))
        {
            $menu->addChild('Расходы', array('route' => 'BackendUchetBundle_homepage'));
            $menu->addChild('Отчет', array('route' => 'BackendUchetBundle_report'));
            $menu->addChild('Категории', array('route' => 'BackendUchetBundle_category'));
            $menu->addChild('Настройки', array('route' => 'BackendUchetBundle_setting'));

            $route = $this->container->get('request')->get('_route');

            if($route == 'default_reportmonth')
            {
                $menu['Отчет']->setLinkAttribute('class', 'active');
            }
        }
        else
        {
            $menu->addChild('Настройки', array('route' => 'BackendUchetBundle_setting'));
        }
        

        return $menu;
    }
}