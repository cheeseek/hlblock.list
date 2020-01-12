<?php \Bitrix\Main\UI\Extension::load('ui.bootstrap4'); ?>
<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php $this->addExternalCss("/bitrix/components/demo/hlblock.list/templates/.default/style.css"); ?>

<div>
    <table class="table table-striped">
        <thead>
            <tr class="redhead">
                <?php foreach ($arResult['FIELDS'] as $FIELD_NAME): ?>
                   <th><?=$FIELD_NAME?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($arResult['ITEMS'] as $ITEM): ?>
            <tr>
                <?php foreach ($arResult['FIELDS'] as $FIELD_CODE => $FIELD_NAME): ?>
                    <td><?=$ITEM[$FIELD_CODE]?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$APPLICATION->IncludeComponent(
    "bitrix:main.pagenavigation",
    "modern", [
    "NAV_OBJECT" => $arResult['NAVIGATION'],
    "COMPONENT_TEMPLATE" => "modern"
],
    false
);
?>