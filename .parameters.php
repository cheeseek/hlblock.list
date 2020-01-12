<?php

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loader::includeModule('highloadblock');

$hlBlockOptions = [];
$listAllHlBlocks = HighloadBlockTable::getList([
    'select' => ['ID', 'NAME'],
    'order'  => ['ID' => 'ASC']
]);

while ($hlBlock = $listAllHlBlocks->fetch()) {
    $hlBlockOptions[$hlBlock['ID']] = "[" . $hlBlock['ID'] . "]" . $hlBlock['NAME'];
}
$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'HLBLOCK_ID'   => [
            'PARENT'   => 'BASE',
            'NAME'     => GetMessage("HLBLOCK_ID"),
            'TYPE'     => 'LIST',
            'VALUES'   => $hlBlockOptions,
            'MULTIPLE' => 'N',
            'REFRESH'  => 'Y',
        ],
        "CACHE_TIME"  =>  [ "DEFAULT" => 3600 ],
    ],
];
