<?php


namespace app\controllers;


use app\models\utils\DownloadHandler;
use app\models\Viber;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DownloadController extends Controller
{
    public function behaviors():array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 404);
                },
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['execution', 'conclusion', 'print-conclusion'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['execution', 'download-temp'],
                        'roles' => ['@', '?'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Скачивание заключения
     * @throws NotFoundHttpException
     */
    public function actionExecution(): void
    {
        DownloadHandler::handleExecution();
    }

    /**
     * Скачивание заключения
     * @param $href string <p>Имя файла в виде 1.pdf</p>
     * @throws NotFoundHttpException <p>В случае отсутствия прав доступа или файла- ошибка</p>
     */
    public function actionConclusion($href): void
    {
        DownloadHandler::handleConclusion($href);
    }

    /**
     * Распечатывание заключения
     * @param $href
     * @throws NotFoundHttpException
     */
    public function actionPrintConclusion($href): void
    {
        DownloadHandler::handleConclusion($href, true);
    }

    /**
     * @param $link
     * @throws NotFoundHttpException
     */
    public function actionDownloadTemp($link): void
    {
        Viber::downloadTempFile($link);
    }
}