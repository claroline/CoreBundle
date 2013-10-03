<?php

namespace Claroline\CoreBundle\Library\Installation\Settings;

class Setting
{
    private $description;
    private $descriptionParameters;
    private $isCorrect;
    private $isRequired;

    /**
     * @param string    $description
     * @param array     $descriptionParameters
     * @param boolean   $isCorrect
     * @param boolean   $isRequired
     */
    public function __construct($description, array $descriptionParameters, $isCorrect, $isRequired)
    {
        $this->description = $description;
        $this->descriptionParameters = $descriptionParameters;
        $this->isCorrect = $isCorrect;
        $this->isRequired = $isRequired;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getDescriptionParameters()
    {
        return $this->descriptionParameters;
    }

    /**
     * @return string
     */
    public function getRawDescription()
    {
        $description = $this->description;

        foreach ($this->descriptionParameters as $name => $parameter) {
            $description = str_replace("%{$name}%", $parameter, $description);
        }

        return $description;
    }

    /**
     * @return boolean
     */
    public function isCorrect()
    {
        return $this->isCorrect;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequired;
    }
}
