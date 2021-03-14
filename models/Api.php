<?php


namespace app\models;


use JsonException;
use RuntimeException;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

class Api
{

    /**
     * Обработка запроса
     * @return array|string[]
     */
    public static function handleRequest(): array
    {
        $request = Yii::$app->getRequest();
        if (!empty($request->bodyParams['cmd'])) {
            $cmd = $request->bodyParams['cmd'];
            switch ($cmd) {
                case 'login' :
                    $login = $request->bodyParams['login'];
                    $pass = $request->bodyParams['pass'];
                    $admin = User::getAdmin();
                    if ($login === $admin->username && $admin->validatePassword($pass)) {
                        // верные данные для входа, верну токен
                        return ['status' => 'success', 'token' => $admin->access_token];
                    }
                    return ['status' => 'failed', 'message' => 'Неверный логин или пароль'];
                case 'check_access_token':
                    if (self::token_valid($request->bodyParams['token'])) {
                        return ['status' => 'success'];
                    }
                    break;
                case 'checkAuthToken':
                    return ['status' => 'in work'];
            }
            return ['status' => 'failed'];
        }

        if (!empty($request->bodyParams['json'])) {
            try {
                $json = json_decode($request->bodyParams['json'], true, 2, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return ['status' => 'error handle json'];
            }
            $cmd = $json['cmd'];
            $token = $json['token'];
            if (!self::token_valid($token)) {
                Telegram::sendDebug("Попытка подключения к API с неверным токеном");
                return ['status' => 'unauthorized'];
            }
            if ($cmd === 'upload_file') {
                $file = UploadedFile::getInstanceByName('my_file');
                if ($file !== null) {
                    // обработаю файл
                    $root = Yii::$app->basePath;
                    // создам временную папку, если её ещё не существует
                    if (!is_dir($root . '/temp') && !mkdir($concurrentDirectory = $root . '/temp') && !is_dir($concurrentDirectory)) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                    }
                    $fileName = $root . "/temp/" . $file->name;
                    $file->saveAs($fileName);
                    try {
                        $path = FileUtils::handleFileUpload($fileName);
                        if ($path !== null) {
                            return ['status' => 'success', 'path' => $path];
                        }
                    } catch (Exception $e) {
                        return ['status' => 'failed', 'message' => $e->getMessage()];
                    }
                }
                return ['status' => 'failed'];
            }
        }
        return ['status' => 'success'];
    }

    private static function token_valid($token): bool
    {
        $user = User::findIdentityByAccessToken($token);
        return $user !== null && $user->username === User::ADMIN_NAME;
    }
}