<?php

declare(strict_types=1);

/*
 * This file is part of Composer File Copier Plugin.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/composer-file-copier-plugin
 */

namespace Markocupic\Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Markocupic\Composer\Plugin\Library\Processor;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected RepositoryManager|null $repositoryManager = null;

    /**
     * If root package is not a project, the plugin will not copy files.
     */
    private bool $isProject = true;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->repositoryManager = $composer->getRepositoryManager();

        if ('project' !== $composer->getPackage()->getType()) {
            $this->isProject = false;
            $io->writeError(
                'Root package is not of type "project", we will not copy files or mirroring folders.'
            );
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // does nothing
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // does nothing
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * The array keys are event names and the value can be:
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     * For instance:
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2')).
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'copyFiles',
            ScriptEvents::POST_UPDATE_CMD => 'copyFiles',
        ];
    }

    public function copyFiles(Event $event): void
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        // If root package is not a project, the plugin will not copy files.
        if (!$this->isProject) {
            return;
        }

        $packages = $this->repositoryManager->getLocalRepository()->getPackages();
        $processed = [];

        if (!empty($packages)) {
            foreach ($packages as $package) {
                if (\in_array($package->getName(), $processed, true)) {
                    continue;
                }

                $processed[] = $package->getName();

                $processor = new Processor($package, $composer, $io);
                $processor->copyResources();
            }
        }
    }
}
