<?php

namespace App\Tests;

class FilesExist extends MissionTest
{
    /**
     * Required mission files.
     *
     * @var array
     */
    protected $files = [
        'config.hpp',
        'version.txt',
        'mission.sqm',
        'description.ext',
    ];

    /**
     * Determines if the test passes.
     *
     * @return boolean
     */
    public function passes($fail, $data)
    {
        $passed = true;

        foreach ($this->files as $file) {
            if (!file_exists("{$this->fullUnpacked}/$file")) {
                $fail("Missing $file");
                $passed = false;
            }
        }

        return $passed;
    }
}
