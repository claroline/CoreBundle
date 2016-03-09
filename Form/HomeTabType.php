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

use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Form\Angular\AngularType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class HomeTabType extends AngularType
{
    private $isAdmin;
    private $workspace;
    private $color;
    private $forApi = false;
    private $ngAlias;

    public function __construct(Workspace $workspace = null, $isAdmin = false, $color = null, $ngAlias = 'htfmc')
    {
        $this->isAdmin = $isAdmin;
        $this->workspace = $workspace;
        $this->color = $color;
        $this->ngAlias = $ngAlias;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('constraints' => new NotBlank(), 'label' => 'name'));
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
        $workspace = $this->workspace;

        if (!is_null($workspace)) {
            $builder->add(
                'roles',
                'entity',
                array(
                    'label' => 'roles',
                    'class' => 'ClarolineCoreBundle:Role',
                    'choice_translation_domain' => true,
                    'query_builder' => function (EntityRepository $er) use ($workspace) {

                        return $er->createQueryBuilder('r')
                            ->where('r.workspace = :workspace')
                            ->setParameter('workspace', $workspace)
                            ->orderBy('r.translationKey', 'ASC');
                    },
                    'property' => 'translationKey',
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                )
            );
        } elseif ($this->isAdmin) {
            $builder->add(
                'roles',
                'entity',
                array(
                    'label' => 'roles',
                    'class' => 'ClarolineCoreBundle:Role',
                    'choice_translation_domain' => true,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.workspace IS NULL')
                            ->andWhere('r.type = 1')
                            ->andWhere('r.name != :anonymousRole')
                            ->setParameter('anonymousRole', 'ROLE_ANONYMOUS')
                            ->orderBy('r.translationKey', 'ASC');
                    },
                    'property' => 'translationKey',
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                )
            );
        }
    }

    public function getName()
    {
        return 'home_tab_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $default = array('translation_domain' => 'platform');

        if ($this->forApi) {
            $default['csrf_protection'] = false;
        }
        $default['ng-model'] = 'homeTab';
        $default['ng-controllerAs'] = $this->ngAlias;

        $resolver->setDefaults($default);
    }

    public function enableApi()
    {
        $this->forApi = true;
    }
}
