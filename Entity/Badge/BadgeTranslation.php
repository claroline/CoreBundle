<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Entity\Badge;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="claro_badge_translation",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="badge_translation_unique_idx", columns={"locale", "badge_id"}),
 *          @ORM\UniqueConstraint(name="badge_name_translation_unique_idx", columns={"name", "locale", "badge_id"}),
 *          @ORM\UniqueConstraint(name="badge_slug_translation_unique_idx", columns={"slug", "locale", "badge_id"})
 *      }
 * )
 * @ExclusionPolicy("all")
 */
class BadgeTranslation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Badge", inversedBy="translations")
     * @ORM\JoinColumn(name="badge_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $badge;

    /**
     * @var string $locale
     *
     * @ORM\Column(type="string", length=8, nullable=false)
     * @Expose
     */
    protected $locale;

    /**
     * @var string $name
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Expose
     * @Assert\Length(max = "128")
     */
    protected $name;

    /**
     * @var string $description
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Expose
     * @Assert\Length(max = "128")
     */
    protected $description;

    /**
     * @var string $slug
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    protected $slug;

    /**
     * @var string $criteria
     *
     * @ORM\Column(type="text", nullable=false)
     * @Expose
     */
    protected $criteria;

    /**
     * @param int $id
     *
     * @return BagdeTranslation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $badge
     *
     * @return BadgeTranslation
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * @return Badge
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * @param string $locale
     *
     * @return BadgeTranslation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $criteria
     *
     * @return BadgeTranslation
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param string $description
     *
     * @return BadgeTranslation
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     *
     * @return BadgeTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $slug
     *
     * @return BadgeTranslation
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }
}
