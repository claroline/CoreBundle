<?php

namespace Claroline\CoreBundle\Form;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('constraints' => new NotBlank()));
        $builder->add(
            'text',
            'textarea',
            array('required' => false, 'attr' => array ('class' => 'tinymce', 'data-theme' => 'advanced')
            )
        );
    }

    public function getName()
    {
        return 'text_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => 'Claroline\CoreBundle\Entity\Resource\Text',
                'translation_domain' => 'platform'
            )
        );
    }
}
