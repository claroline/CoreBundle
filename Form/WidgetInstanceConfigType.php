<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form;

use Claroline\CoreBundle\Form\Angular\AngularType;
use Claroline\CoreBundle\Repository\WidgetRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WidgetInstanceConfigType extends AngularType
{
    private $isDesktop;
    private $withRole;
    private $roles;
    private $color;
    private $textTitleColor;
    private $forApi = false;
    private $ngAlias;
    private $creationMode;

    public function __construct(
        $isDesktop = true,
        $withRole = false,
        array $roles = array(),
        $color = null,
        $textTitleColor = null,
        $ngAlias = 'wfmc',
        $creationMode = true
    )
    {
        $this->isDesktop = $isDesktop;
        $this->withRole = $withRole;
        $this->roles = $roles;
        $this->color = $color;
        $this->textTitleColor = $textTitleColor;
        $this->ngAlias = $ngAlias;
        $this->creationMode = $creationMode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'label' => 'name',
                'constraints' => new NotBlank()
            )
        );

        if ($this->creationMode) {
            $datas = array();
            $datas['is_desktop'] = $this->isDesktop;
            $datas['with_role'] = $this->withRole;
            $datas['roles'] = $this->roles;

            $builder->add(
                'widget',
                'entity',
                array(
                    'label' => 'type',
                    'class' => 'Claroline\CoreBundle\Entity\Widget\Widget',
                    'choice_translation_domain' => true,
                    'translation_domain' => 'widget',
                    'expanded' => false,
                    'multiple' => false,
                    'constraints' => new NotBlank(),
                    'query_builder' => function (WidgetRepository $widgetRepo) use ($datas) {
                        if ($datas['is_desktop']) {

                            if ($datas['with_role']) {

                                return $widgetRepo->createQueryBuilder('w')
                                    ->join('w.roles', 'r')
                                    ->where('w.isDisplayableInDesktop = true')
                                    ->andWhere("r IN (:roles)")
                                    ->setParameter('roles', $datas['roles']);

                            } else {

                                return $widgetRepo->createQueryBuilder('w')
                                    ->where('w.isDisplayableInDesktop = true');
                            }
                        } else {
                            return $widgetRepo->createQueryBuilder('w')
                                ->where('w.isDisplayableInWorkspace = true');
                        }
                    }
                )
            );
        }
        $builder->add(
            'color',
            'text',
            array(
                'required' => false,
                'mapped' => false,
                'label' => 'color',
                'data' => $this->color,
                'attr' => array('colorpicker' => 'hex')
            )
        );
        $builder->add(
            'textTitleColor',
            'text',
            array(
                'required' => false,
                'mapped' => false,
                'label' => 'text_title_color',
                'data' => $this->textTitleColor,
                'attr' => array('colorpicker' => 'hex')
            )
        );
    }

    public function getName()
    {
        return 'widget_instance_config_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $default = array('translation_domain' => 'platform');

        if ($this->forApi) {
            $default['csrf_protection'] = false;
        }
        $default['ng-model'] = 'widgetInstance';
        $default['ng-controllerAs'] = $this->ngAlias;

        $resolver->setDefaults($default);
    }

    public function enableApi()
    {
        $this->forApi = true;
    }
}
