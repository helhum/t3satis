<?php

/*
 * This file is part of Satis.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *     Nils Adermann <naderman@naderman.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace helhum\T3Satis\Console;

use Composer\Composer;
use Composer\Satis\Command;
use Helhum\T3Satis\Composer\Factory;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Application extends \Composer\Satis\Console\Application
{

    /**
     * @return Composer
     */
    public function getComposer($required = true, $config = null)
    {
        if (null === $this->composer) {
            try {
                $this->composer = Factory::create($this->io, $config);
            } catch (\InvalidArgumentException $e) {
                $this->io->write($e->getMessage());
                exit(1);
            }
        }

        return $this->composer;
    }
}
