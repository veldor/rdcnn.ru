<?php


namespace app\models;


use app\models\utils\GrammarHandler;
use JsonException;
use RuntimeException;
use Throwable;
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
                case 'userLogin':
                    $login = GrammarHandler::toLatin($request->bodyParams['login']);
                    $pass = $request->bodyParams['pass'];
                    $user = User::findByUsername($login);
                    if ($user !== null) {
                        if ($user->failed_try < 10) {
                            if ($user->validatePassword($pass)) {
                                return ['status' => 'success', 'auth_token' => $user->access_token, 'execution_id' => $user->username];
                            }
                            ++$user->failed_try;
                            $user->save();
                        }
                        Telegram::sendDebug("try to enter in {$user->username} with password {$pass}");
                    }
                    return ['status' => 'failed', 'message' => 'wrong data'];
                case 'checkAuthToken':
                    $authToken = $request->bodyParams['authToken'];
                    if (!empty($authToken)) {
                        $user = User::findIdentityByAccessToken($authToken);
                        if ($user !== null) {
                            return ['status' => 'success', 'execution_id' => $user->username];
                        }
                    }
                    return ['status' => 'failed', 'message' => 'invalid token'];
                case 'get_execution_info':
                    $authToken = $request->bodyParams['token'];
                    if (!empty($authToken)) {
                        $user = User::findIdentityByAccessToken($authToken);
                        if ($user !== null) {
                            $filesInfo = Table_availability::getFilesInfo($user);
                            return [
                                'status' => 'success',
                                'execution_id' => $user->username,
                                'patient_name' => Table_availability::getPatientName($user->username),
                                'files' => $filesInfo
                            ];
                        }
                        return ['status' => 'failed', 'message' => 'invalid token'];
                    }
            }
            return ['status' => 'failed', 'message' => 'unknown action'];
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

    /**
     * @throws JsonException
     */
    public static function handleFileRequest(): void
    {
        $request = Yii::$app->getRequest();
        $cmd = $request->bodyParams['cmd'];
        if($cmd === 'get_file'){
            $authToken = $request->bodyParams['token'];
            if (!empty($authToken)) {
                $user = User::findIdentityByAccessToken($authToken);
                if ($user !== null) {
                    $file = $request->bodyParams['file_name'];
                    Telegram::sendDebug("handling file " . $file);
                    try {
                        FileUtils::loadFile($file);
                    } catch (Throwable $e) {
                        Telegram::sendDebug($e->getMessage());
                    }
                }
            }
        }
    }
}