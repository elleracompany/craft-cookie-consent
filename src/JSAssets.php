<?php

namespace elleracompany\cookieconsent;

class JSAssets extends \craft\web\AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@elleracompany/cookieconsent/resources';

        $this->js = [
            'script.js',
        ];

        parent::init();
    }
}
