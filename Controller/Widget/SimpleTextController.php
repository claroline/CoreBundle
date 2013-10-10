<?php

namespace Claroline\CoreBundle\Controller\Widget;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Claroline\CoreBundle\Form\Factory\FormFactory;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Entity\Widget\SimpleTextConfig;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SimpleTextController extends Controller
{
    /**
     * @EXT\Route(
     *     "/simple_text_update/config/{widget}",
     *     name="claro_simple_text_update_config"
     * )
     * @EXT\Method("POST")
     */
    public function updateSimpleTextWidgetConfig(WidgetInstance $widget, Request $request)
    {
        if (!$this->get('security.context')->isGranted('edit', $widget)) {
            throw new AccessDeniedException();
        }

       $simpleTextConfig = $this->get('claroline.manager.simple_text_manager')->getTextConfig($widget);
       //wtf !
       $id = array_pop(array_keys($request->request->all()));
       $form = $this->get('claroline.form.factory')->create(FormFactory::TYPE_SIMPLE_TEXT, array($id));
       $form->bind($this->getRequest());

       if ($form->isValid()) {
           $formDatas = $form->get('content')->getData();
           $content = is_null($formDatas) ? '' : $formDatas;

           if ($simpleTextConfig) {
               $simpleTextConfig->setContent($content);
           } else {
               $simpleTextConfig = new SimpleTextConfig();
               $simpleTextConfig->setWidgetInstance($widget);
               $simpleTextConfig->setContent($content);
           }
       } else {
            $simpleTextConfig = new SimpleTextConfig();
            $simpleTextConfig->setWidgetInstance($widget);
            $errorForm = $this->container->get('claroline.form.factory')
                ->create(FormFactory::TYPE_SIMPLE_TEXT, array('widget_text_'.rand(0, 1000000000), $simpleTextConfig));
            $errorForm->setData($form->getData());
            $children = $form->getIterator();
            $errorChildren = $errorForm->getIterator();

            foreach ($children as $key => $child) {
                $errors = $child->getErrors();
                foreach ($errors as $error) {
                    $errorChildren[$key]->addError($error);
                }
            }

           return $$this->render(
               'ClarolineCoreBundle:Widget:config_simple_text_form.html.twig',
               array(
                   'form' => $errorForm->createView(),
                   'config' => $widget
               )
           );
       }

       $em = $this->get('doctrine.orm.entity_manager');
       $em->persist($simpleTextConfig);
       $em->flush();

       return new Response('success', 204);
    }
}
