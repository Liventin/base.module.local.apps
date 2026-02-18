<?php

/** @noinspection PhpUnused */

namespace Base\Module\Install;


use Base\Module\Exception\ModuleException;
use Base\Module\Service\LocalApps\LocalAppEntity;
use Base\Module\Service\Tool\ClassList;
use Base\Module\Install\Interface\Install;
use Base\Module\Install\Interface\UnInstall;
use Base\Module\Install\Interface\ReInstall;
use Base\Module\Service\Container;
use Base\Module\Service\LocalApps\LocalAppsService as ILocalAppService;

class LocalAppsInstaller implements Install, UnInstall, ReInstall
{
    /**
     * @return array
     * @throws ModuleException
     */
    private function getLocalApps(): array
    {
        /** @var ClassList $classList */
        $classList = Container::get(ClassList::SERVICE_CODE);
        return $classList->setSubClassesFilter([LocalAppEntity::class])->getFromLib('LocalApps');
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function install(): void
    {
        /** @var ILocalAppService $handlersService */
        $handlersService = Container::get(ILocalAppService::SERVICE_CODE);
        $handlersService->setLocalAppList($this->getLocalApps())->install();
    }

    /**
     * @param bool $saveData
     * @return void
     * @throws ModuleException
     */
    public function unInstall(bool $saveData): void
    {
        /** @var ILocalAppService $handlersService */
        $handlersService = Container::get(ILocalAppService::SERVICE_CODE);
        $handlersService->setLocalAppList($this->getLocalApps())->unInstall($saveData);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function reInstall(): void
    {
        /** @var ILocalAppService $handlersService */
        $handlersService = Container::get(ILocalAppService::SERVICE_CODE);
        $handlersService->setLocalAppList($this->getLocalApps())->reInstall();
    }

    public function getInstallSort(): int
    {
        return 125;
    }

    public function getUnInstallSort(): int
    {
        return 125;
    }

    public function getReInstallSort(): int
    {
        return 125;
    }
}
