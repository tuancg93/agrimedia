<?php

namespace Agrimedia\Anhpt;


use Nwidart\Modules\Generators\ModuleGenerator;

class AgrimediaModuleGenerator extends ModuleGenerator
{

    public function generateResources()
    {

        $this->console->call('module:make-repository', [
            'name' => $this->getName() . 'Repository',
            '--master' => true,
            'module' => $this->getName(),
        ]);
        parent::generateResources();
    }


}
