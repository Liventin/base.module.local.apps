<?php

namespace Base\Module\Service\LocalApps;

interface LocalAppsService
{
    public const SERVICE_CODE = 'base.module.local.apps.service';

    public function setLocalAppList(array $localAppList): self;
    public function install(): void;
    public function unInstall(bool $saveData): void;
    public function reInstall(): void;
    public function getAppCode(string $appName): ?string;
}