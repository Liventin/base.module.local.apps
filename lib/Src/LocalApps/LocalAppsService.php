<?php

namespace Base\Module\Src\LocalApps;

use Base\Module\Exception\ModuleException;
use Base\Module\Service\LazyService;
use Base\Module\Service\LocalApps\LocalAppEntity;
use Base\Module\Service\LocalApps\LocalAppsService as ILocalAppService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Preset\IntegrationTable;
use Exception;

#[LazyService(serviceCode: ILocalAppService::SERVICE_CODE, constructorParams: [])]
class LocalAppsService implements ILocalAppService
{
    /**
     * @var LocalAppEntity[]
     */
    private array $localAppList = [];
    private ?string $host;
    private ?array $installedAppsData = null;

    public function __construct()
    {
        $this->host = Context::getCurrent()?->getServer()->getServerName();
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getAppCode(string $appName): ?string
    {
        $this->prepareInstalledApps();
        return $this->installedAppsData[$appName];
    }

    public function setLocalAppList(array $localAppList): self
    {
        $this->localAppList = $localAppList;
        return $this;
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws Exception
     */
    public function install(): void
    {
        $appsNames = [];
        foreach ($this->localAppList as $localAppEntity) {
            $appsNames[] = $localAppEntity::getAppName();
        }

        if (empty($appsNames)) {
            return;
        }

        $this->prepareInstalledApps();

        foreach ($this->localAppList as $localAppEntity) {
            $appName = $localAppEntity::getAppName();
            if (array_key_exists($appName, $this->installedAppsData)) {
                $appId = $this->installedAppsData[$appName]['ID'];
            } else {
                $appId = $this->addApp($localAppEntity);
            }

            $this->addIntegrationTable($appId, $localAppEntity);
        }
    }

    public function unInstall(bool $saveData): void
    {
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function reInstall(): void
    {
        $this->install();
    }

    /**
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function prepareInstalledApps(): void
    {
        if ($this->installedAppsData !== null) {
            return;
        }

        /** @var Query $query */
        $query = AppTable::query();
        $this->installedAppsData = array_column(
            $query
                ->addSelect('ID')
                ->addSelect('APP_NAME')
                ->addSelect('CODE')
                ->fetchAll(),
            null,
            'APP_NAME'
        );
    }

    /**
     * @param LocalAppEntity $appEntity
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @throws Exception
     */
    private function addApp(string $appEntity): int
    {
        $appName = $appEntity::getAppName();
        $appScope = $appEntity::getScope();
        $appUrl = $appEntity::getTemplatePath() . '/' . $appName . '/';

        $appData = [
            'APP_NAME' => $appName,
            'APPLICATION_TOKEN' => $this->host,
            'STATUS' => AppTable::STATUS_LOCAL,
            'SCOPE' => implode(',', $appScope),
            'URL' => "https://$this->host/" . $appUrl,
            'INSTALLED' => AppTable::INSTALLED,
            'ACTIVE' => AppTable::ACTIVE,
        ];

        $result = AppTable::add($appData);

        if (!$result->isSuccess()) {
            throw new ModuleException(
                'cant add app: ' . $appName . ' ' .
                implode('; ', $result->getErrorMessages())
            );
        }

        $appId = $result->getId();
        AppTable::install($appId);
        return $appId;
    }

    /**
     * @param int $appId
     * @param LocalAppEntity $localAppEntity
     * @return void
     * @throws Exception
     * @noinspection PhpDocSignatureInspection
     */
    private function addIntegrationTable(int $appId, string $localAppEntity): void
    {
        $appName = $localAppEntity::getAppName();
        $appScope = $localAppEntity::getScope();

        $integrationData = [
            'USER_ID' => 1,
            'PASSWORD_ID' => 1,
            'ELEMENT_CODE' => 'application',
            'TITLE' => $appName,
            'APP_ID' => $appId,
            'SCOPE' => $appScope,
        ];

        /** @var Query $query */
        $query = IntegrationTable::query();
        $fetch = $query
            ->addSelect('ID')
            ->whereIn('TITLE', $appName)
            ->fetch();

        if ($fetch === false) {
            $bdResult = IntegrationTable::add($integrationData);
        } else {
            $bdResult = IntegrationTable::update($fetch['ID'], $integrationData);
        }

        if (!$bdResult->isSuccess()) {
            throw new ModuleException(
                'cant add/update in integration table app: ' . $appName . ' ' .
                implode('; ', $bdResult->getErrorMessages())
            );
        }
    }
}