<?php

namespace Claroline\CoreBundle\Form\Badge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BadgeTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'badge_form_name'
            ))
            ->add('description', 'text', array(
                'label' => 'badge_form_description'
            ))
            ->add('criteria', 'tinymce', array(
                'label' => 'badge_form_criteria'
            ))
            ->add('locale', 'hidden');
    }

    public function getName()
    {
        return 'badge_translation_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                array(
                    'data_class'         => 'Claroline\CoreBundle\Entity\Badge\BadgeTranslation',
                    'translation_domain' => 'badge'
                )
            );
    }
}
