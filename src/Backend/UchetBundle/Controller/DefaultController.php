<?php

namespace Backend\UchetBundle\Controller;

use MyProject\Proxies\__CG__\stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Backend\UchetBundle\Entity\Costs;
use Backend\UchetBundle\Entity\Categories;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Backend\UchetBundle\PDO\PdoSqliteConnect;
use Symfony\Component\Yaml\Yaml;
use Backend\UchetBundle\AppClass\UchetScenario;

class DefaultController extends Controller
{
    //Главная страница
    public function indexAction(Request $request)
    {
        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);
        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }


        $costs    = new Costs();
        $category = new Categories();
        $doctrine = $this->getDoctrine();

        //get array category id, name
        $choices = $category->getCategoryChoices($doctrine);

        $form = $this->createFormBuilder($costs)
            ->add('name', TextType::class,['label' => 'Название', 'required' => false])
            ->add('catid', ChoiceType::class,['label' => 'Категория', 'required' => false,'choices' => $choices,'choices_as_values' => true, 'placeholder' => 'Выбрать',])
            ->add('sum', MoneyType::class,['label' => 'Сумма', 'required' => false, 'currency' => 'RUR','invalid_message' => 'Это значение не является действительным.'])
            ->add('date', DateType::class,['widget' => 'single_text', 'label' => 'Дата', 'required' => false, 'html5' => false,'format' => 'dd.MM.yyyy','input' => 'timestamp', 'invalid_message' => 'Это значение не является действительным.','attr' => ['class' => 'datepicker', 'value' => date('d.m.Y', time())],])
            ->add('save', SubmitType::class, [
                        'label' => 'Добавить','attr' =>
                        [ 'class' => 'btn-primary']
                     ]
                 )
            ->getForm();

        $request = Request::createFromGlobals();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           $costs->save($doctrine, $costs);
            return $this->redirectToRoute('BackendUchetBundle_homepage');
        }

        //get row category all
        $costs_all = $costs->getCostsAll($doctrine);

        return $this->render('BackendUchetBundle:Default:index.html.twig', array(
            'form'  => $form->createView(),
            'costs' => $costs_all,
        ));
        

    }

    //Страница категории
    public function categoryAction(Request $request)
    {
        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);
        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        $category = new Categories();
        $doctrine = $this->getDoctrine();

        $form = $this->createFormBuilder($category)
        ->add('name', TextType::class,['label' => 'Название', 'required' => false])
        ->add('save', SubmitType::class, [
                'label' => 'Добавить','attr' => [
                    'class' => 'btn-primary'
                ]
            ]
        )
        ->getForm();

        $request = Request::createFromGlobals();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->save($doctrine, $category);
            return $this->redirectToRoute('BackendUchetBundle_category');
        }

        //get row category all
        $category_all = $category->getCategoryAll($doctrine,'id','DESC');

        return $this->render('BackendUchetBundle:Default:category.html.twig',[
            'form' => $form->createView(),
            'category' => $category_all,
        ]);
    }

    //Страница редактирования категории
    public function editAction($id, Request $request)
    {
        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);
        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        $category = new Categories();
        $doctrine = $this->getDoctrine()->getManager();
        $cat = $doctrine->getRepository('BackendUchetBundle:Categories')->find($id);

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class,['label' => 'Название', 'required' => false, 'attr' => ['value' => $cat->getName()]])
            ->add('save', SubmitType::class, [
                    'label' => 'Сохранить','attr' => [
                        'class' => 'btn-primary'
                    ]
                ]
            )
            ->getForm();

        $request = Request::createFromGlobals();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cat->setName($category->getName());
            $doctrine->flush();
            return $this->redirectToRoute('BackendUchetBundle_category');
        }


        return $this->render('BackendUchetBundle:Default:edit.html.twig',[
            'category' => $cat,
            'form' => $form->createView(),
        ]);
    }


    //Страница отчета
    public function reportAction()
    {
        $cfg = Yaml::parse(
            file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'),
            false,
            true
        );

        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        $db = new PdoSqliteConnect();
        $dbconnect = $db->connect($this->container);

        $rows = $dbconnect->prepare("
                  select strftime('%m', datetime(date, 'unixepoch', 'localtime')) month, 
                  date, 
                  sum(sum) fullsum 
                  from costs 
                  GROUP BY month");
        $rows->execute();
        
        $report = $rows->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('BackendUchetBundle:Default:report.html.twig',[
            'report' => $report,
        ]);
    }

    //Страница детального отчета за месяц
    public function reportmonthAction($month)
    {
        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);
        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        $category = new Categories();
        $doctrine = $this->getDoctrine();

        //get array category id, name
        $arr_category = $category->getCategoryAll($doctrine,'name','ASC');

        $m = mktime(0, 0, 0, $month, 1, 0);

        $db = new PdoSqliteConnect();
        $dbconnect = $db->connect($this->container);

        $rows = $dbconnect->prepare("
                  select 
                  strftime('%d', datetime(date, 'unixepoch', 'localtime')) day,
                  strftime('%m', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum, 
                  date, 
                  categories.id catid,
                  categories.name catname
                  from costs 
                  LEFT JOIN categories 
                  ON costs.catid = categories.id 
                  WHERE month = '" . $month . "' order by day asc");

        $rows->execute();

        $result = $rows->fetchAll(\PDO::FETCH_ASSOC);

        foreach($result as $k => $v)
        {
            $dcat[$v['catid']]['name'] = $v['catname'];
            $dcat[$v['catid']]['sum_column'][] = $v['sum'];
            $arrsum[] = $v['sum'];
        }

        foreach ($dcat as $item) {
            foreach($result as $k => $v)
            {
                if($item['name'] == $v['catname'])
                {
                    $ddate[$v['day']]['category'][$item['name']][] = $v['sum'];
                    $ddate[$v['day']]['sum_row'][] = $v['sum'];
                }
                else
                {
                    $ddate[$v['day']]['category'][$item['name']][] = '';
                }

            }
        }

        return $this->render('BackendUchetBundle:Default:reportmonth.html.twig',[
            'month'   => $m,
            'category' => $dcat,
            'date' => $ddate,
            'total' => $arrsum,
        ]);
    }

    //Страница настроек
    public function settingAction(Request $request)
    {
        $cfg = Yaml::parse(file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'), false, true);

        $form = $this->createFormBuilder($cfg)
            ->add('limit', MoneyType::class,[
                'label'           => 'Предельная сумма на месяц',
                'required'        => false,
                'currency'        => 'RUR',
                'invalid_message' => 'Это значение не является действительным.',
                'constraints'     => [ new NotBlank(['message' => "Поле не должно быть пустым."])]
            ])
            ->add('scenario', ChoiceType::class,[
                'label'       => 'Сценарий предела',
                'required'    => false,
                'choices'     => ['Адаптивный предел', 'Увеличение предела'],
                'expanded'    => true,
                'placeholder' => false,
                'constraints' => [ new NotBlank(['message' => "Нужно выбрать вариант."])]
            ])
            ->add('save', SubmitType::class, [
                    'label' => 'Сохранить','attr' => [
                        'class' => 'btn-primary'
                    ]
                ]
            )
            ->getForm();

        $request = Request::createFromGlobals();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $object = new \stdClass();
            $object->limit   = $request->get('form')['limit'];
            $object->scenario = $request->get('form')['scenario'];

            $yaml = Yaml::dump($object, 2, 4, false, true);

            file_put_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml', $yaml);

            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        return $this->render('BackendUchetBundle:Default:setting.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    //Страница - увеличение лимита
    public function raiseAction(Request $request)
    {
        $cfg = Yaml::parse(
            file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'),
            false,
            true
        );


        if(!isset($cfg->limit)){
            return $this->redirectToRoute('BackendUchetBundle_setting');
        }

        $raise = null;

        if(isset($cfg->raise_limit[date('m', time())])){
            $raise = $cfg->raise_limit[date('m', time())];
        }

        $raise_form = $this->createFormBuilder()
            ->add('raise_limit', MoneyType::class,[
                'label'           => 'Предельную сумму увеличить на:',
                'required'        => false,
                'currency'        => 'RUR',
                'invalid_message' => 'Это значение не является действительным.',
                'constraints'     => [ new NotBlank(['message' => "Поле не должно быть пустым."])],
                'attr'            => ['value' => $raise],
            ])
            ->add('raise_save', SubmitType::class, [
                    'label' => 'Сохранить',
                    'attr'  => [
                        'class' => 'btn-primary'
                    ]
                ]
            )
            ->getForm();


        $request = Request::createFromGlobals();

        $raise_form->handleRequest($request);

        if ($raise_form->isSubmitted() && $raise_form->isValid()) {

            $cfg->raise_limit = [date('m', time()) => $request->get('form')['raise_limit']];

            $yaml = Yaml::dump($cfg, 2, 4, false, true);

            file_put_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml', $yaml);

            return $this->redirectToRoute('BackendUchetBundle_homepage');
        }


        return $this->render('BackendUchetBundle:Default:raiselimitform.html.twig', [
            'raise_form' => $raise_form->createView(),
        ]);
    }

    //Вывод в сайдбар информации о текущем положении лимита, расходов...
    public function sidebarrightAction(Request $request)
    {
        $cfg = Yaml::parse(
            file_get_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml'),
            false,
            true
        );

        if(!isset($cfg->limit)){
            return $this->render('BackendUchetBundle:Default:sidebarright.html.twig', [
                'sidebar_show' => false,
            ]);
        }

        $UchetScenario  = new UchetScenario();
        $getScenario    = $UchetScenario->getScenario($cfg, $this->container);
  
        $limit          = $getScenario->limit;
        $sum_cost       = $getScenario->sum_cost;
        $balance        = $getScenario->balance;
        $msg            = $getScenario->msg;
        $sum_next_month = $getScenario->sum_next_month;
        $excess         = $getScenario->excess;
        $prevexcess     = $getScenario->prevexcess;
        $raise          = null;

        if(isset($cfg->raise_limit[date('m', time())])){
            $raise = $cfg->raise_limit[date('m', time())];
        }

        return $this->render('BackendUchetBundle:Default:sidebarright.html.twig', [
            'sum_month'         => $limit,
            'sum_cost'          => $sum_cost,
            'balance'           => $balance,
            'msg_show'          => $msg,
            'sum_next_month'    => $sum_next_month,
            'excess'            => $excess,
            'prevexcess'        => $prevexcess,
            'scenario'          => $cfg->scenario,
            'sidebar_show'      => true,
            'raise'             => $raise,
        ]);
    }



}
