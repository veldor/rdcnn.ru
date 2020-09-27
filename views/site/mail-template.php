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

        a {
            text-decoration: none;
        }

        * .btn {
            display: inline-block;
            margin-bottom: 5px;
            font-weight: normal;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            background-image: none;
            border: 1px solid transparent;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            border-radius: 4px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            color: #fff !important;
        }

        *.btn-primary {
            background-color: #337ab7;
            border-color: #2e6da4;
        }

        *.btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        *.btn-info {
            background-color: #5bc0de;
            border-color: #46b8da;
        }

    </style>
    <title></title>
</head>
<body class="text-center">
<table id="main-table" class="text-center">
    <tbody>
    <tr>
        <td colspan="2" class="text-center">
            <img alt="logo image" src="https://rdcnn.ru/images/logo.png"/>
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
            <h3 class="text-center">У вас есть вопросы?</h3>
            <p class="text-center"><a class="btn btn-primary" href="tel:2020200">Кликните, чтобы позвонить нам (2020200)</a></p>
            <p class="text-center"><a class="btn btn-primary" href="mailto:clinica@rdcnn.ru">Кликните, чтобы написать нам</a></p>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>




