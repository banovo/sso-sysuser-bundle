<?php

namespace Banovo\SSOSysuserBundle;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Sysuser extends AbstractGuardAuthenticator
{

    private $sso_config = [];

    public function __construct($sso_config)
    {
        $this->sso_config = $sso_config;

        dump($this->sso_config);
        die();

    }

}
