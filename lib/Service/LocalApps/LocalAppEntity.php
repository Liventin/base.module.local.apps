<?php

namespace Base\Module\Service\LocalApps;

interface LocalAppEntity
{
    public static function getAppName(): string;
    public static function getTemplatePath(): string;
    public static function getScope(): array;
}