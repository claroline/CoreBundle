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

use Claroline\CoreBundle\Entity\Badge\Badge;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @DI\Service("claroline.form.badge")
 */
class BadgeType extends AbstractType
{
    /** @var \Claroline\CoreBundle\Form\Badge\BadgeRuleType */
    private $badgeRuleType;

    /** @var \Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler */
    private $platformConfigHandler;

    /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator */
    private $translator;

    /**
     * @DI\InjectParams({
     *     "badgeRuleType"         = @DI\Inject("claroline.form.badge.rule"),
     *     "platformConfigHandler" = @DI\Inject("claroline.config.platform_config_handler"),
     *     "translator"            = @DI\Inject("translator")
     * })
     */
    public function __construct(
        BadgeRuleType $badgeRuleType,
        PlatformConfigurationHandler $platformConfigHandler,
        Translator $translator
    )
    {
        $this->badgeRuleType         = $badgeRuleType;
        $this->platformConfigHandler = $platformConfigHandler;
        $this->translator            = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('frTranslation', new BadgeTranslationType())
            ->add('enTranslation', new BadgeTranslationType())
            ->add('automatic_award', 'checkbox', array('required' => false))
            ->add('file', 'file', array('label' => 'badge_form_image'))
            ->add('is_expiring', 'checkbox', array('required' => false))
            ->add('expire_duration', 'integer', array('attr' =>
                array(
                      'class' => 'input-sm',
                      'min'   => 1
                )
            ))
            ->add('expire_period', 'choice',
                array(
                    'choices'     => Badge::getExpirePeriodLabels(),
                    'attr'        => array('class' => 'input-sm')
                )
            )
            ->add(
                'rules',
                'collection',
                array(
                    'type'          => $this->badgeRuleType,
                    'by_reference'  => false,
                    'attr'          => array('class' => 'rule-collections'),
                    'theme_options' => array('label_width' => 'col-md-3'),
                    'prototype'     => true,
                    'allow_add'     => true,
                    'allow_delete'  => true
                )
            );
    }

    public function getName()
    {
        return 'badge_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Claroline\CoreBundle\Entity\Badge\Badge',
                'translation_domain' => 'badge',
                'language'           => 'en',
                'date_format'        => DateType::HTML5_FORMAT,
                'cascade_validation' => true
            )
        );
    }
}
