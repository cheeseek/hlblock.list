<?php
namespace Demo\HighloadBlock;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\LoaderException;
use Bitrix\Highloadblock\HighloadBlockTable;
use CBitrixComponent;
use COption;
use CUserTypeEntity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Exception;
use CPHPCache;
use Bitrix\Main\UI\PageNavigation;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CList extends CBitrixComponent
{
    /**
     * Count items limit on page
     */
    const LIMIT = 10;

    /**
     * Highload block id parameter name
     */
    const HLBLOCK_ID = 'HLBLOCK_ID';

    /**
     * Highload block entity prefix
     */
    const HLBLOCK_ENTITY_PREFIX = 'HLBLOCK_';

    /**
     * Column label type
     */
    const COLUMN_LABEL_TYPE = 'LIST_COLUMN_LABEL';

    /**
     * Page navigation parameter in URL
     */
    const PAGE = 'page';

    /**
     * Default cache time
     */
    const DEFAULT_CACHE_TIME = 3600;

    /**
     * @return void
     * @throws LoaderException
     */
    public function executeComponent()
    {
        try {
            $this->checkModules();
            if (!isset($this->arParams["CACHE_TIME"])) {
                $this->arParams["CACHE_TIME"] = self::DEFAULT_CACHE_TIME;
            }
            if ($this->arParams["CACHE_TYPE"] == "Y"
                || ($this->arParams["CACHE_TYPE"] == "A"
                    && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"
                )
            ) {
                $this->arParams["CACHE_TIME"] = intval($this->arParams["CACHE_TIME"]);
            }
            else {
                $this->arParams["CACHE_TIME"] = 0;
            }

            $cache_id ='demo_highloadBlock_' . hash('sha256',serialize($this->arParams) . $_GET[self::PAGE]);
            $obCache = new CPHPCache;

            if ($obCache->InitCache($this->arParams['CACHE_TIME'], $cache_id, '/')) {
                $vars = $obCache->GetVars();
                $this->arResult = $vars['arResult'];
            } elseif ($obCache->StartDataCache()) {
                $this->generateResult();
                $obCache->EndDataCache([
                    'arResult' => $this->arResult,
                ]);
            }
            $this->includeComponentTemplate();
        } catch (Exception $exception) {
            ShowError($exception->getMessage());
        }
    }

    /**
     * Check Module Enabled
     *
     * @return bool
     * @throws LoaderException
     */
    private function checkModules()
    {
        return Loader::includeModule('highloadblock');
    }

    /**
     * Generate result
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function generateResult()
    {
        if ($hlBlockId = $this->arParams[self::HLBLOCK_ID]) {
            $hlDataClass = $this->getHlDataClass($hlBlockId);
            $items = $hlDataClass::getList([
                'select' => ["ID"],
            ]);
            $itemsCount = $items->getSelectedRowsCount();
            $navigation = $this->generateNavigation($itemsCount);
            $currentPageItemsData = $hlDataClass::getList([
                'select' => ["*"],
                'order' => ['ID'],
                'limit' => $navigation->getLimit(),
                'offset' => $navigation->getOffset()
            ]);

            $this->arResult['ITEMS'] = $this->getHlItems($currentPageItemsData);
            $this->arResult['FIELDS'] = $this->getHlFields($currentPageItemsData);
        }
    }

    /**
     * Get HL-block Data Class
     *
     * @param $HlBlockId
     * @return DataManager
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    private function getHlDataClass($HlBlockId)
    {
        $hlBlock = HighloadBlockTable::getById($HlBlockId)->fetch();
        $entity = HighloadBlockTable::compileEntity($hlBlock);

        return $entity->getDataClass();
    }

    /**
     * Get HL-block items
     *
     * @param $resultItemsData
     * @return array
     */
    private function getHlItems($resultItemsData)
    {
        $hlItems = [];
        while($resultItem = $resultItemsData->fetch()) {
            $hlItems[] = $resultItem;
        }

        return $hlItems;
    }

    /**
     * Get HL-block fields
     *
     * @param $resultItemsData
     * @return array
     */
    private function getHlFields($resultItemsData)
    {
        $fieldIds = [];
        $resultFields = $resultItemsData->getFields();
        foreach ($resultFields as $fieldKey => $fieldObj) {
            $fieldIds[] = $fieldKey;
        }

        return $this->getHlFieldNamesByIds($fieldIds);
    }

    /**
     * Get translated field names from user fields by field ids
     *
     * @param $fieldIds
     * @return array
     */
    private function getHlFieldNamesByIds($fieldIds)
    {
        $entityName = self::HLBLOCK_ENTITY_PREFIX . $this->arParams[self::HLBLOCK_ID];
        $translatedFieldNames = CUserTypeEntity::GetList(
            [],
            [
                'ENTITY_ID' => $entityName,
                'FIELD_NAME' => $fieldIds,
                'LANG' => LANGUAGE_ID
            ]
        );
        $fieldLabels['ID'] = 'ID';
        while ($translatedFieldName = $translatedFieldNames->fetch()) {
            $fieldLabels[$translatedFieldName['FIELD_NAME']] = $translatedFieldName[self::COLUMN_LABEL_TYPE];
        }

        return $fieldLabels;
    }

    /**
     * Generate navigation parameters
     *
     * @param int $itemsCount
     * @return PageNavigation
     */
    private function generateNavigation($itemsCount)
    {
        $navigation = new PageNavigation(self::PAGE);
        $navigation->allowAllRecords(true)
            ->setPageSize(self::LIMIT)
            ->setRecordCount($itemsCount)
            ->initFromUri();

        return $this->arResult['NAVIGATION'] = $navigation;
    }
}
