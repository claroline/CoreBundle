<?php

namespace Claroline\CoreBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;

/**
 * @DI\Service("claroline.manager.simple_text_manager")
 */
class SimpleTextManager
{
    private $om;

   /**
    * @DI\InjectParams({
    *       "om" = @DI\Inject("claroline.persistence.object_manager")
    * })
    */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function getTextConfig(WidgetInstance $config)
    {
        return $this->om
            ->getRepository('ClarolineCoreBundle:Widget\SimpleTextConfig')
            ->findOneBy(array('widgetInstance' => $config));
    }
}
