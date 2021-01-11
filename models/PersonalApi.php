<?php


namespace app\models;


use app\models\database\FirebaseToken;
use app\models\database\PersonalItems;
use app\models\database\PersonalTask;
use app\models\utils\FirebaseHandler;
use JsonException;
use Yii;

class PersonalApi
{
    private static array $data;

    /**
     * Обработка запроса
     * @return array|string[]
     * @throws JsonException
     * @throws exceptions\MyException
     */
    public static function handleRequest(): array
    {
        if(!empty($_POST)){
            return ['status' => 'success', 'message' => serialize($_POST)];
        }
        self::$data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!empty(self::$data['cmd'])) {
            switch (self::$data['cmd']) {
                case 'login':
                    return self::login();
                case 'getTaskList':
                    return self::getTaskList();
                case 'getIncomingTaskList':
                    return self::getIncomingTaskList();
                case 'newTask':
                    return self::createNewTask();
                case 'getTaskInfo':
                    return self::getTaskInfo();
                case 'confirmTask':
                    return self::confirmTask();
                case 'cancelTask':
                    return self::cancelTask();
                case 'finishTask':
                    return self::finishTask();
                case 'getNewTasks':
                    return self::getNewTasks();
                case 'dismissTask':
                    return self::dismissTask();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function login(): array
    {
        $login = self::$data['login'];
        $password = self::$data['pass'];
        $firebaseToken = self::$data['firebase_token'];
        $user = PersonalItems::findOne(['login' => $login]);
        if ($user !== null) {
            if (Yii::$app->security->validatePassword($password, $user->pass_hash)) {
                // добавлю токен
                FirebaseToken::add($user->id, $firebaseToken);
                // всё верно, верну токен доступа
                Telegram::sendDebug("Успешный вход: " . $user->name);
                return ['status' => 'success', 'token' => $user->access_token, 'role' => $user->role];
            }
            Telegram::sendDebug("Неудачная попытка входа " . $user->name . ", логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
            return ['status' => 'failed', 'message' => 'invalid data'];
        }
        Telegram::sendDebug("Неудачная попытка входа, логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getTaskList(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $list = PersonalTask::getTaskList($user->id);
                return ['status' => 'success', 'list' => $list];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function createNewTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                // добавлю новую задачу
                $theme = self::$data['title'];
                $text = self::$data['text'];
                $target = self::$data['target'];
                $t = 0;
                switch ($target) {
                    case 'IT-отдел':
                        $t = 2;
                        break;
                    case 'Инженерная служба':
                        $t = 3;
                        break;
                    case 'Офис':
                        $t = 4;
                        break;
                }
                $task = new PersonalTask();
                $task->initiator = $user->id;
                $task->task_header = $theme ?: 'Без названия';
                $task->task_body = $text;
                $task->task_creation_time = time();
                $task->task_status = 'created';
                $task->target = $t;
                $task->save();
                FirebaseHandler::sendTaskCreated($task);
                Telegram::sendDebug("Добавлена новая задача");
                return ['status' => 'success'];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getIncomingTaskList(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $tasks = PersonalTask::getTasksForExecutor($user);
                return ['status' => 'success', 'list' => $tasks];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return array|string[]
     * @throws exceptions\MyException
     */
    private static function getTaskInfo(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                return ['status' => 'success', 'task_info' => PersonalTask::getTaskInfo($taskId)];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws exceptions\MyException
     */
    private static function confirmTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                $plannedTime = self::$data['plannedTime'];
                PersonalTask::setTaskConfirmed($taskId, $plannedTime, $user);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws exceptions\MyException
     */
    private static function cancelTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                PersonalTask::setTaskCancelled($taskId);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getNewTasks(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                return ['status' => 'success', 'new_tasks_count' => PersonalTask::findNew($user)];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    /**
     * @return string[]
     * @throws exceptions\MyException
     */
    private static function finishTask(): array
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                PersonalTask::setTaskFinished($taskId);
                return self::getTaskInfo();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function dismissTask()
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if (!empty($token)) {
            $user = PersonalItems::findOne(['access_token' => $token]);
            if ($user !== null) {
                $taskId = self::$data['taskId'];
                $reason = self::$data['reason'];
                PersonalTask::setTaskDismissed($taskId, $reason);
                return ['status' => 'success'];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }
}