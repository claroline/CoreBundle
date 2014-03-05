<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form\Badge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @DI\Service("claroline.form.badge.award")
 */
class BadgeAwardType extends AbstractType
{
    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    private $translator;

    /**
     * @DI\InjectParams({
     *     "router"     = @DI\Inject("router"),
     *     "translator" = @DI\Inject("translator")
     * })
     */
    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router     = $router;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('group', 'zenstruck_ajax_entity', array(
                'placeholder'    => $this->translator->trans('badge_award_form_group_choose', array(), 'badge'),
                'class'          => 'ClarolineCoreBundle:Group',
                'use_controller' => true,
                'property'       => 'name',
                'repo_method'    => 'findByNameForAjax'
            ))
            ->add('user', 'zenstruck_ajax_entity', array(
                'placeholder'    => $this->translator->trans('badge_award_form_user_choose', array(), 'badge'),
                'class'          => 'ClarolineCoreBundle:User',
                'use_controller' => true,
                'property'       => 'username',
                'repo_method'    => 'findByNameForAjax'
            ));
    }

    public function getName()
    {
        return 'badge_award_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'badge'));
    }
}
