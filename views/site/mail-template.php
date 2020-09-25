<?php



/* @var $text string $ */


use app\priv\Info;

?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style type="text/css">
        #main-table {
            max-width: 600px;
            width: 100%;
            margin: auto;
            padding: 0;
        }

        .text-center {
            text-align: center;
        }
    </style>
    <title></title>
</head>
<body>
<table id="main-table">
    <tbody>
    <tr>
        <td colspan="2">
            <h1>Тут будет хедер</h1>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <?= $text ?>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <!--Футер-->
            <hr/>
            <h3 class="text-center">Тут футер</h3>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>




