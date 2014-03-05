<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Entity;

use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\EventRepository")
 * @ORM\Table(name="claro_event")
 */
class Event
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(length=50)
     */
    private $title;

    /**
     * @ORM\Column(name="start_date", type="integer", nullable=true)
     */
    private $start;

    /**
     * @ORM\Column(name="end_date", type="integer", nullable=true)
     */
    private $end;

    /**
     * @ORM\Column(nullable=true, type="text")
     */
    private $description;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace",
     *     inversedBy="events",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $workspace;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(name="allday", type="boolean", nullable=true)
     */
    private $allDay;

     /**
     * @ORM\Column(nullable=true)
     */
    private $priority;
    private $recurring;
    private $startHours;
    private $endHours;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getStart()
    {
        if (is_null($this->start)) {
            return $this->start;

        } else {
            $date = date('d-m-Y H:i', $this->start);

            return (new \Datetime($date));
        }
    }

    public function setStart($start)
    {
        if (!is_null($start)) {
            if ($start instanceof \Datetime) {
                $this->start = $start->getTimestamp();
            }
        }
    }

    public function getEnd()
    {
        if (is_null($this->end)) {
            return $this->end;

        } else {
            $date = date('d-m-Y H:i', $this->end);

            return (new \Datetime($date));
        }
    }

    public function setEnd($end)
    {
        if (!is_null($end)) {
            if ($end instanceof \Datetime) {
                $this->end = $end-> getTimestamp();
            }
        }
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getWorkspace()
    {
        return $this->workspace;
    }

    public function setWorkspace(AbstractWorkspace $workspace)
    {
        $this->workspace = $workspace;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getAllDay()
    {
        return $this->allDay;
    }

    public function setAllDay($allDay)
    {
        $this->allDay = (bool) $allDay;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }
    public function getRecurring()
    {
        return $this->recurring;
    }

    public function setRecurring($recurring)
    {
        $this->recurring = $recurring;
    }

    public function getStartHours()
    {
        return $this->startHours;
    }

    public function setStartHours($startHours)
    {
        $this->startHours = $startHours;
    }

    public function getEndHours()
    {
        return $this->endHours;
    }

    public function setEndHours($endHours)
    {
        $this->endHours = $endHours;
    }
}
