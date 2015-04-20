<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form\Field;

use Claroline\CoreBundle\Form\DataTransformer\UserPickerTransfromer;
use Claroline\CoreBundle\Manager\UserManager;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @DI\Service("claroline.form.user_picker")
 * @DI\FormType(alias = "userpicker")
 */
class UserPickerType extends AbstractType
{
    private $userManager;
    private $userPickerTransformer;

    /**
     * @DI\InjectParams({
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "userPickerTransformer" = @DI\Inject("claroline.transformer.user_picker")
     * })
     */
    public function __construct(
        UserManager $userManager,
        UserPickerTransfromer $userPickerTransformer
    )
    {
        $this->userManager = $userManager;
        $this->userPickerTransformer = $userPickerTransformer;
    }

    public function getName()
    {
        return 'userpicker';
    }

    public function getParent()
    {
        return 'text';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->userPickerTransformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        $user = $this->getUser($form);

        if ($user instanceof User) {
            $view->vars['attr']['data-user-id'] = $user->getId();
            $view->vars['attr']['data-username'] = $user->getUsername();
            $view->vars['attr']['data-first-name'] = $user->getFirstName();
            $view->vars['attr']['data-last-name'] = $user->getLastName();
        }
    }

    private function getUser(FormInterface $form)
    {
        $data = $form->getData();

        if ($data instanceof User) {

            return $form->getData();
        } elseif (!empty($data)) {

            return $this->userManager->getUserById($data);
        }
    }
}