<?php


function getTableByUserSettings($table, $content, $x = null)
{

}

function createHead($content, $keys): string
{
    // Добавляем строки с данными
    foreach ($content as $th) {
        $row .= <<<HTML
        <tr>
            <th>{$th['']}</th>
        </tr>
        HTML;
    }

    return <<<HTML
    <thead>
        $row
    </thead>
    HTML;
}

function createBody($content, $keys)
{

}