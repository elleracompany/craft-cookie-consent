<?php

namespace elleracompany\cookieconsent;

class CSSAssets extends \craft\web\AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@elleracompany/cookieconsent/resources';

        $this->css = [
            'style.css',
        ];

        parent::init();
    }
}
