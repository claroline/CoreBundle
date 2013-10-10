<?php

namespace Claroline\CoreBundle\Form;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TextType extends AbstractType
{
    private $formName;

    public function __construct($formName = null)
    {
        $this->formName = $formName;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array('label' => 'text_form_name', 'constraints' => new NotBlank()));
        $builder->add('text', 'textarea', array('label' => 'text_form_text', 'attr' => array ('class' => 'tinymce', 'data-theme' => 'advanced')));
    }

    public function getName()
    {
        return $this->formName;
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
