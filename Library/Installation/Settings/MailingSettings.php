<?php

namespace Claroline\CoreBundle\Library\Installation\Settings;

class MailingSettings extends AbstractValidator
{
    private $transport = 'smtp';
    private $transportOptions = array();
    private $blankOptions = array(
        'host' => null,
        'username' => null,
        'password' => null,
        'auth_mode' => null,
        'encryption' => null,
        'port' => null
    );

    public function setTransport($transport)
    {
        $this->transport = trim($transport);
    }

    public function getTransport()
    {
        return $this->transport;
    }

    public function setTransportOptions(array $options)
    {
        $trimmedOptions = array();

        foreach ($options as $option => $value) {
            if (array_key_exists($option, $this->blankOptions)) {
                $trimmedOptions[$option] = trim($value) ?: null;
            }
        }

        $this->transportOptions = array_merge($this->blankOptions, $trimmedOptions);
    }

    public function getTransportOptions()
    {
        return $this->transportOptions;
    }

    public function getTransportOption($option)
    {
        if (array_key_exists($option, $this->transportOptions)) {
            return $this->transportOptions[$option];
        }

        return null;
    }

    protected function doValidate()
    {
        if ($this->checkIsNotBlank('transport', $this->transport)
            && $this->checkIsValidMailTransport('transport', $this->transport)) {
            if ($this->transport === 'sendmail') {
                return; // nothing to validate
            } elseif ($this->transport === 'gmail') {
                $this->checkIsNotBlank('username', $this->transportOptions['username']);
                $this->checkIsNotBlank('password', $this->transportOptions['password']);
            } else { // smtp
                $this->checkIsNotBlank('host', $this->transportOptions['host']);
                $this->checkIsValidMailEncryption('encryption', $this->transportOptions['encryption']);
                $this->checkIsValidMailAuthMode('auth_mode', $this->transportOptions['auth_mode']);

                if (!empty($this->transportOptions['port'])) {
                    $this->checkIsPositiveNumber('port', $this->transportOptions['port']);
                }
            }
        }
    }
}
