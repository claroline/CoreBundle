<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Event\Log;

use Claroline\CoreBundle\Entity\User;

class LogHomeTabUserDeleteEvent extends LogGenericEvent
{
    const ACTION = 'user-home-tab-delete';

    /**
     * Constructor.
     */
    public function __construct(User $user, $details = array())
    {
        parent::__construct(
            self::ACTION,
            $details,
            $user
        );
    }

    /**
     * @return array
     */
    public static function getRestriction()
    {
        return array(self::PLATFORM_EVENT_TYPE);
    }
}
