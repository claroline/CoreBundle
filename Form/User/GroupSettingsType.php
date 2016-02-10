<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form\User;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Claroline\CoreBundle\Form\User\GroupType;
use Claroline\CoreBundle\Entity\Role;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupSettingsType extends GroupType
{
    public function __construct($isAdmin = true)
    {
        parent::__construct();
        $this->isAdmin = $isAdmin;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $isAdmin = true;
        $builder->add(
            'platformRoles',
            'entity',
            array(
                'label' => 'roles',
                'class' => 'Claroline\CoreBundle\Entity\Role',
                'choice_translation_domain' => true,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'property' => 'translationKey',
                'disabled' => false,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($isAdmin){
                    $query = $er->createQueryBuilder('r')
                        ->where("r.type = " . Role::PLATFORM_ROLE)
                        ->andWhere("r.name != 'ROLE_ANONYMOUS'")
                        ->andWhere("r.name != 'ROLE_USER'");

                    if (!$isAdmin) {
                        $query->andWhere("r.name != 'ROLE_ADMIN'");
                    }

                    return $query;
                }
            )
        );
    }

    public function getName()
    {
        return 'group_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
    }
}