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

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Rule\Rulable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Claroline\CoreBundle\Form\Badge\Constraints as BadgeAssert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * @ORM\Table(name="claro_badge")
 * @ORM\Entity(repositoryClass="Claroline\CoreBundle\Repository\Badge\BadgeRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @BadgeAssert\AutomaticWithRules
 * @BadgeAssert\HasImage
 * @BadgeAssert\AtLeastOneTranslation
 * @BadgeAssert\CheckExpiringPeriod
 * @ExclusionPolicy("all")
 */
class Badge extends Rulable
{
    use SoftDeleteableEntity;

    const EXPIRE_PERIOD_DAY       = 0;
    const EXPIRE_PERIOD_DAY_LABEL = 'day';
    const EXPIRE_PERIOD_WEEK       = 1;
    const EXPIRE_PERIOD_WEEK_LABEL = 'week';
    const EXPIRE_PERIOD_MONTH       = 2;
    const EXPIRE_PERIOD_MONTH_LABEL = 'month';
    const EXPIRE_PERIOD_YEAR       = 3;
    const EXPIRE_PERIOD_YEAR_LABEL = 'year';

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
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=false)
     * @Expose
     * @Assert\NotBlank()
     * @Assert\GreaterThan(value = 0)
     */
    protected $version = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="automatic_award", type="boolean", nullable=true)
     * @Expose
     */
    protected $automaticAward;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", nullable=false)
     * @Expose
     */
    protected $imagePath;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_expiring", type="boolean", options={"default": 0})
     */
    protected $isExpiring = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="expire_duration", type="integer", nullable=true)
     * @Assert\GreaterThan(value = 0)
     */
    protected $expireDuration;

    /**
     * @var integer
     *
     * @ORM\Column(name="expire_period", type="smallint", nullable=true)
     */
    protected $expirePeriod;

    /**
     * @var UploadedFile
     *
     * @Assert\Image(
     *     maxSize = "256k",
     *     minWidth = 64,
     *     minHeight = 64
     * )
     */
    protected $file;

    /**
     * @var string
     */
    protected $olfFileName = null;

    /**
     * @var ArrayCollection|UserBadge[]
     *
     * @ORM\OneToMany(targetEntity="Claroline\CoreBundle\Entity\Badge\UserBadge", mappedBy="badge", cascade={"all"})
     */
    protected $userBadges;

    /**
     * @var ArrayCollection|BadgeClaim[]
     *
     * @ORM\OneToMany(targetEntity="Claroline\CoreBundle\Entity\Badge\BadgeClaim", mappedBy="badge", cascade={"all"})
     */
    protected $badgeClaims;

    /**
     * @var ArrayCollection|BadgeRule[]
     *
     * @ORM\OneToMany(targetEntity="Claroline\CoreBundle\Entity\Badge\BadgeRule", mappedBy="associatedBadge", cascade={"persist"})
     * @Expose
     */
    protected $badgeRules;

    /**
     * @var AbstractWorkspace
     *
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace")
     * @ORM\JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    protected $workspace;

    /**
     * @var ArrayCollection|BadgeTranslation[]
     *
     * @ORM\OneToMany(
     *   targetEntity="Claroline\CoreBundle\Entity\Badge\BadgeTranslation",
     *   mappedBy="badge",
     *   cascade={"all"}
     * )
     * @Expose
     */
    protected $translations;

    /**
     * @var null
     */
    protected $locale = null;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->userBadges   = new ArrayCollection();
        $this->badgeRules   = new ArrayCollection();
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getUsers()
    {
        $users = new ArrayCollection();

        foreach ($this->userBadges as $userBadge) {
            $users[] = $userBadge->getUser();
        }

        return $users;
    }

    /**
     * @param User[]|ArrayCollection $users
     *
     * @return $this
     */
    public function setUsers($users)
    {
        $this->userBadges->clear();

        foreach ($users as $user) {
            $userBagde = new UserBadge();

            $userBagde
                ->setBadge($this)
                ->setUser($user);

            $this->addUserBadge($userBagde);
        }

        return $this;
    }

    /**
     * @return UserBadge[]|ArrayCollection
     */
    public function getUserBadges()
    {
        return $this->userBadges;
    }

    /**
     * @param UserBadge $userBadge
     *
     * @return Badge
     */
    public function addUserBadge(UserBadge $userBadge)
    {
        if (!$this->userBadges->contains($userBadge)) {
            $this->userBadges[] = $userBadge;
        }

        return $this;
    }

    /**
     * @param UserBadge $userBadge
     *
     * @return bool
     */
    public function removeUserBadge(UserBadge $userBadge)
    {
        return $this->userBadges->removeElement($userBadge);
    }

    /**
     * @return BadgeTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     * @return BadgeTranslation|null
     */
    public function getTranslationForLocale($locale)
    {
        foreach ($this->getTranslations() as $translation) {
            if ($locale === $translation->getLocale()) {
                return $translation;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown translation for locale %s.', $locale));
    }

    /**
     * @return BadgeTranslation|null
     */
    public function getFrTranslation()
    {
        return $this->getTranslationForLocale('fr');
    }

    /**
     * @return BadgeTranslation|null
     */
    public function getEnTranslation()
    {
        return $this->getTranslationForLocale('en');
    }

    public function setFrTranslation(BadgeTranslation $badgeTranslation)
    {

    }

    /**
     * @param  BadgeTranslation $translation
     * @return Badge
     */
    public function addTranslation(BadgeTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            $this->translations[] = $translation;
            $translation->setBadge($this);
        }

        return $this;
    }

    /**
     * @param  BadgeTranslation $translation
     * @return Badge
     */
    public function removeTranslation(BadgeTranslation $translation)
    {
        $this->translations->removeElement($translation);

        return $this;
    }

    /**
     * @param int $id
     *
     * @return Badge
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
     * @param null|string $locale
     *
     * @return Badge
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     * @return null|string
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            throw new \InvalidArgumentException('No locale setted for badge translation.');
        }

        return $this->locale;
    }

    /**
     * @param string $imagePath
     *
     * @return Badge
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param int $version
     *
     * @return Badge
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getName($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        return $this->getTranslationForLocale($locale)->getName();
    }

    /**
     * @return string
     */
    public function getFrName()
    {
        return $this->getName('fr');
    }

    /**
     * @return string
     */
    public function getEnName()
    {
        return $this->getName('en');
    }

    /**
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        return $this->getTranslationForLocale($locale)->getDescription();
    }

    /**
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getSlug($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        return $this->getTranslationForLocale($locale)->getSlug();
    }

    /**
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getCriteria($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        return $this->getTranslationForLocale($locale)->getCriteria();
    }

    /**
     * @param boolean $automaticAward
     *
     * @return Badge
     */
    public function setAutomaticAward($automaticAward)
    {
        $this->automaticAward = $automaticAward;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAutomaticAward()
    {
        return $this->automaticAward;
    }

    /**
     * @param \Claroline\CoreBundle\Entity\Badge\BadgeRule[] $badgeRules
     *
     * @return Badge
     */
    public function setRules($badgeRules)
    {
        foreach ($badgeRules as $rule) {
            $rule->setAssociatedBadge($this);
        }

        $this->badgeRules = $badgeRules;

        return $this;
    }

    /**
     * @return \Claroline\CoreBundle\Entity\Badge\BadgeRule[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getRules()
    {
        return $this->badgeRules;
    }

    /**
     * @param AbstractWorkspace $workspace
     *
     * @return Badge
     */
    public function setWorkspace(AbstractWorkspace $workspace)
    {
        $this->workspace = $workspace;

        return $this;
    }

    /**
     * @return AbstractWorkspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param boolean $isExpiring
     *
     * @return Badge
     */
    public function setIsExpiring($isExpiring)
    {
        $this->isExpiring = $isExpiring;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsExpiring()
    {
        return $this->isExpiring;
    }

    /**
     * @return bool
     */
    public function isExpiring()
    {
        return $this->getIsExpiring();
    }

    /**
     * @param int $expireDuration
     *
     * @return Badge
     */
    public function setExpireDuration($expireDuration)
    {
        $this->expireDuration = $expireDuration;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpireDuration()
    {
        return $this->expireDuration;
    }

    /**
     * @param int $expirePeriod
     *
     * @return Badge
     */
    public function setExpirePeriod($expirePeriod)
    {
        $this->expirePeriod = $expirePeriod;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpirePeriod()
    {
        return $this->expirePeriod;
    }

    /**
     * @return string
     */
    public function getExpirePeriodLabel()
    {
        return self::getExpirePeriodTypeLabel($this->expirePeriod);
    }

    /**
     * @return array
     */
    public static function getExpirePeriodTypes()
    {
        return array(self::EXPIRE_PERIOD_DAY,
                     self::EXPIRE_PERIOD_WEEK,
                     self::EXPIRE_PERIOD_MONTH,
                     self::EXPIRE_PERIOD_YEAR);
    }

    /**
     * @return array
     */
    public static function getExpirePeriodLabels()
    {
        return array(self::EXPIRE_PERIOD_DAY_LABEL,
                     self::EXPIRE_PERIOD_WEEK_LABEL,
                     self::EXPIRE_PERIOD_MONTH_LABEL,
                     self::EXPIRE_PERIOD_YEAR_LABEL);
    }

    /**
     * @param integer $expirePeriodType
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function getExpirePeriodTypeLabel($expirePeriodType)
    {
        $expirePeriodLabels = self::getExpirePeriodLabels();

        if (!isset($expirePeriodLabels[$expirePeriodType])) {
            throw new \InvalidArgumentException("Unknown expired period type.");
        }

        return $expirePeriodLabels[$expirePeriodType];
    }

    /**
     * @param UploadedFile $file
     *
     * @return Badge
     */
    public function setFile(UploadedFile $file)
    {
        $newFileName = $file->getClientOriginalName();

        if ($this->imagePath !== $newFileName) {
            $this->olfFileName = $this->imagePath;
            $this->imagePath   = null;
        }
        $this->file = $file;

        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * @return null|string
     */
    public function getAbsolutePath()
    {
        return (null === $this->imagePath) ? null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->imagePath;
    }

    /**
     * @return null|string
     */
    public function getWebPath()
    {
        return (null === $this->imagePath) ? null : $this->getUploadDir() . DIRECTORY_SEPARATOR . $this->imagePath;
    }

    /**
     * @throws \Exception
     * @return string
     */
    protected function getUploadRootDir()
    {
        $ds = DIRECTORY_SEPARATOR;

        $uploadRootDir = sprintf(
            '%s%s..%s..%s..%s..%s..%s..%s..%sweb%s%s',
            __DIR__, $ds, $ds, $ds, $ds, $ds, $ds, $ds, $ds, $ds, $this->getUploadDir()
        );
        $realpathUploadRootDir = realpath($uploadRootDir);

        if (false === $realpathUploadRootDir) {
            throw new \Exception(
                sprintf(
                    "Invalid upload root dir '%s'for uploading badge images.",
                    $uploadRootDir
                )
            );
        }

        return $realpathUploadRootDir;
    }

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        return sprintf("uploads%sbadges", DIRECTORY_SEPARATOR);
    }

    protected function dealWithAtLeastOneTranslation(ObjectManager $objectManager)
    {
        $frTranslation = $this->getFrTranslation();
        $enTranslation = $this->getEnTranslation();

        $frName        = $frTranslation->getName();
        $frDescription = $frTranslation->getDescription();
        $frCriteria    = $frTranslation->getCriteria();

        $enName        = $enTranslation->getName();
        $enDescription = $enTranslation->getDescription();
        $enCriteria    = $enTranslation->getCriteria();

        // Have to put all method call in variable because of empty doesn't
        // support result of method as parameter (prior to PHP 5.5)
        $hasFrTranslation = !empty($frName) && !empty($frDescription) && !empty($frCriteria);
        $hasEnTranslation = !empty($enName) && !empty($enDescription) && !empty($enCriteria);

        if (!$hasFrTranslation && !$hasEnTranslation) {
            throw new \Exception('At least one translation must be defined on the badge');
        }

        if (!$hasFrTranslation || !$hasEnTranslation) {
            if ($hasFrTranslation) {
                $enTranslation
                    ->setLocale('en')
                    ->setName($frName)
                    ->setDescription($frDescription)
                    ->setCriteria($frCriteria);
            } elseif ($hasEnTranslation) {
                $frTranslation
                    ->setLocale('fr')
                    ->setName($enName)
                    ->setDescription($enDescription)
                    ->setCriteria($enCriteria);
            }
        }
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $this->dealWithAtLeastOneTranslation($event->getObjectManager());
        if (null !== $this->file) {
            $this->imagePath = $this->file->getClientOriginalName();
        }
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $this->dealWithAtLeastOneTranslation($event->getObjectManager());
        if (null !== $this->file) {
            $this->imagePath = $this->file->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostUpdate()
     */
    public function postUpdate()
    {
        if (null === $this->file) {
            return;
        }

        $this->file->move($this->getUploadRootDir(), $this->imagePath);

        if (null !== $this->olfFileName && is_file($this->olfFileName)) {
            unlink($this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->olfFileName);
            $this->olfFileName = null;
        }

        $this->file = null;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist()
    {
        if (null === $this->file) {
            return;
        }

        $this->file->move($this->getUploadRootDir(), $this->imagePath);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function postRemove()
    {
        $filePath = $this->getAbsolutePath();
        if (null !== $filePath && is_file($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @return array
     */
    public function getRestriction()
    {
        $restriction = array();
        if (null !== $this->getWorkspace()) {
            $restriction['workspace'] = $this->getWorkspace();
        }

        return $restriction;
    }
}
