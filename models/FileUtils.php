<?php


namespace app\models;


use app\models\utils\GrammarHandler;
use Exception;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Yii;

class FileUtils
{
    public const FOLDER_WAITING_TIME = 300;

    /**
     * Метод проверяет нераспознанные директории файлов и возвращает список нераспознанных
     * На случай ошибок администраторов в обзывании папок
     * @return array
     */
    public static function checkUnhandledFolders(): array
    {
        // это список нераспознанных папок
        $unhandledFoldersList = [];
        // паттерн валидных папок
        $pattern = '/^[aа]?\d+$/ui';
        // получу список папок с заключениями
        $dirs = array_slice(scandir(Yii::getAlias('@executionsDirectory')), 2);
        foreach ($dirs as $dir) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $dir;
            if (is_dir($path)) {
                // если папка не соответствует принятому названию- внесу её в список нераспознанных
                // отфильтрую свежесозданные папки: они могут быть ещё в обработке
                $stat = stat($path);
                $changeTime = $stat['mtime'];
                $difference = time() - $changeTime;
                if ($difference > self::FOLDER_WAITING_TIME && !preg_match($pattern, $dir)) {
                    $unhandledFoldersList[] = $dir;
                }
            }
        }
        return $unhandledFoldersList;
    }

    public static function deleteUnhandledFolder(): void
    {
        // получу имя папки
        $folderName = Yii::$app->request->post('folderName');
        if (!empty($folderName)) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $folderName;
            if (is_dir($path)) {
                self::removeDir($path);
            }
        }
    }

    public static function removeDir($path)
    {
        if (is_file($path)) {
            return unlink($path);
        }
        if (is_dir($path)) {
            foreach (scandir($path, SCANDIR_SORT_NONE) as $p) {
                if (($p !== '.') && ($p !== '..')) {
                    self::removeDir($path . DIRECTORY_SEPARATOR . $p);
                }
            }
            return rmdir($path);
        }
        return false;
    }

    public static function renameUnhandledFolder(): void
    {
        $oldFolderName = Yii::$app->request->post('oldName');
        $newFolderName = Yii::$app->request->post('newName');
        if (!empty($oldFolderName)) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $oldFolderName;
            if (is_dir($path)) {
                rename($path, Yii::getAlias('@executionsDirectory') . '\\' . $newFolderName);
            }
        }

    }

    /**
     * Получение списка папок, ожидающих обработки
     * @return array <p>Возвращает список имён папок</p>
     */
    public static function checkWaitingFolders(): array
    {
        // это список ожидающих папок
        $waitingFoldersList = [];
        // паттерн валидных папок
        $pattern = '/^[aа]?\d+$/ui';
        // получу список папок с заключениями
        $dirs = array_slice(scandir(Yii::getAlias('@executionsDirectory')), 2);
        foreach ($dirs as $dir) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $dir;
            // если папка не соответствует принятому названию- внесу её в список нераспознанных
            // отфильтрую свежесозданные папки: они могут быть ещё в обработке
            if (is_dir($path) && preg_match($pattern, $dir)) {
                $waitingFoldersList[] = $dir;
            }
        }
        return $waitingFoldersList;
    }

    /**
     * @return string
     */
    public static function getUpdateInfo(): string
    {
        $file = Yii::$app->basePath . '\\logs\\update.log';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 'file is empty';
    }
    /**
     * @return string
     */
    public static function getJavaInfo(): string
    {
        $file = Yii::$app->basePath . '\\logs\\java_info_error.log';
        if (is_file($file)) {
            return GrammarHandler::convertToUTF(file_get_contents($file));
        }
        return 'file is empty';
    }

    /**
     * @return string
     */
    public static function getOutputInfo(): string
    {
        $file = Yii::$app->basePath . '\\logs\\content_change.log';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 'file is empty';
    }

    /**
     * @return string
     */
    public static function getErrorInfo(): string
    {
        $file = Yii::$app->basePath . '\\logs\\content_change_err.log';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 'file is empty';
    }

    public static function setUpdateInProgress(): void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, '1');
    }

    public static function setUpdateFinished(): void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, '0');
    }

    public static function isUpdateInProgress(): bool
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        if (is_file($file)) {
            $content = file_get_contents($file);
            if((bool) $content){
                // проверю, что с момента последнего обновления прошло не больше 15 минут. Если больше- сброшу флаг
                $lastTime = self::getLastUpdateTime();
                return !(time() - $lastTime > 900);
            }
            return false;
        }
        return false;
    }

    public static function setLastUpdateTime(): void
    {
        $file = Yii::$app->basePath . '\\priv\\last_update_time.conf';
        file_put_contents($file, time());
    }

    public static function getLastUpdateTime(): int
    {
        $file = Yii::$app->basePath . '\\priv\\last_update_time.conf';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 0;
    }

    /**
     * @param $text
     */
    public static function writeUpdateLog($text): void
    {
        try {
            $logPath = Yii::$app->basePath . '\\logs\\update.log';
            $newContent = $text . "\n";
            if (is_file($logPath)) {
                // проверю размер лога
                $content = file_get_contents($logPath);
                if (!empty($content) && $content !== '') {
                    $notes = mb_split("\n", $content);
                    if (!empty($notes) && count($notes) > 0) {
                        $notesCounter = 0;
                        foreach ($notes as $note) {
                            if ($notesCounter > 30) {
                                break;
                            }
                            $newContent .= $note . "\n";
                            ++$notesCounter;
                        }
                    }
                }
            }
            file_put_contents($logPath, $newContent);
        } catch (Exception $e) {
        }
    }

    public static function getServiceErrorsInfo()
    {
        $logPath = Yii::$app->basePath . '\\errors\\errors.txt';
        if (is_file($logPath)) {
            return file_get_contents($logPath);
        }
        return 'no errors';
    }

    public static function getUpdateOutputInfo()
    {
        $outFilePath = Yii::$app->basePath . '\\logs\\update_file.log';
        if (is_file($outFilePath)) {
            return file_get_contents($outFilePath);
        }
        return 'no info';
    }

    public static function getUpdateErrorInfo()
    {

        $outFilePath = Yii::$app->basePath . '\\logs\\update_err.log';
        if (is_file($outFilePath)) {
            return file_get_contents($outFilePath);
        }
        return 'no errors';
    }

    public static function setLastCheckUpdateTime(): void
    {
        $file = Yii::$app->basePath . '\\priv\\last_check_update_time.conf';
        file_put_contents($file, time());
    }

    public static function getLastCheckUpdateTime(): int
    {
        $file = Yii::$app->basePath . '\\priv\\last_check_update_time.conf';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 0;
    }

    public static function addBackgroundToPDF($file): void
    {
        $pdfBackgoundImage = Yii::$app->basePath . '\\design\\back.jpg';
        if (is_file($file) && is_file($pdfBackgoundImage)) {
            $pdf = new Fpdi();

            $pdf->AddPage();

            $pdf->Image($pdfBackgoundImage, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight());
            try {
                $pdf->setSourceFile($file);
                $tplIdx = $pdf->importPage(1);
                //use the imported page and place it at point 0,0; calculate width and height
//automaticallay and ajust the page size to the size of the imported page
                $pdf->useTemplate($tplIdx, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight(), true);

                $pageCounter = 2;
                // попробую добавить оставшиеся страницы
                while (true) {
                    try {
                        $tplIdx = $pdf->importPage($pageCounter);
                        $pdf->AddPage();
                        //use the imported page and place it at point 0,0; calculate width and height
//automaticallay and ajust the page size to the size of the imported page
                        $pdf->useTemplate($tplIdx, 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight(), true);
                        ++$pageCounter;
                    } catch (Exception $e) {
                        break;
                    }
                }
                $tempFileName = $file . '_tmp';
                $pdf->Output($tempFileName, 'F');
                unlink($file);
                rename($tempFileName, $file);
            } catch (PdfParserException $e) {
            } catch (PdfReaderException $e) {
            }
        }
    }
    public static function handleLoadedFile(string $loadedFile): ?string
    {
        $existentJavaPath = null;
        $javaPath = 'C:\Program Files (x86)\Java\jre1.8.0_241\bin\java.exe';
        if(is_file($javaPath)){
            echo 'founded java path ' . $javaPath . "\n";
            $existentJavaPath = $javaPath;
        }
        else{
            $javaPath = 'C:\Program Files (x86)\Java\jre1.8.0_251\bin\java.exe';
            if(is_file($javaPath)){
                echo 'founded java path ' . $javaPath . "\n";
                $existentJavaPath = $javaPath;
            }
            else{
                $javaPath = 'C:\Program Files\Java\jre1.8.0_241\bin\java.exe';
                if(is_file($javaPath)){
                    echo 'founded java path ' . $javaPath . "\n";
                    $existentJavaPath = $javaPath;
                }
            }
        }

        if($existentJavaPath !== null){
            // проверю наличие обработчика
            $handler = Yii::$app->basePath . '\\java\\docx_to_pdf_converter.jar';
            $conclusionsDir = Yii::getAlias('@conclusionsDirectory');
            if(is_file($handler) && is_file($loadedFile) && is_dir($conclusionsDir)){
                $command = "\"$existentJavaPath\" -jar $handler \"$loadedFile\" \"$conclusionsDir\"";
                echo $command;
                exec($command, $result);
                if($result === null){
                    return 'null result';
                }
                if(empty($result)){
                    return 'empty answer';
                }
                return implode(' ', $result);
        }

           /* if(!empty($result) && count($result) === 2){
                // получу вторую строку результата
                $fileName = $result[1];
                if(substr($fileName, strlen($fileName) - 4) === '.pdf'){
                    return ['filename' => $fileName, 'action_status' => GrammarHandler::convertToUTF($result[0])];
                }
            }
            if(!empty($result) && count($result) === 1){
                return ['action_status' => GrammarHandler::convertToUTF($result[0])];
            }*/
        }
        else{
            echo 'not found handler';
        }
        return null;
    }

    /**
     * @param string $downloadedFile
     * @param $extension
     * @return string
     * @throws \yii\base\Exception
     */
    public static function saveTempFile(string $downloadedFile, $extension):string
    {
        $root = Yii::$app->basePath;
        // создам временную папку, если её ещё не существует
        if(!is_dir($root . '/temp') && !mkdir($concurrentDirectory = $root . '/temp') && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $fileName = Yii::$app->security->generateRandomString() . $extension;
        file_put_contents($root . "/temp/{$fileName}", $downloadedFile);
        return $root . "/temp/{$fileName}";
    }

    /**
     * @param $file
     * @return string
     * @throws \yii\base\Exception
     */
    public static function handleFileUpload($file): string
    {
        // попробую обработать файл
        $actionResult = self::handleLoadedFile($file);
        if($actionResult === null){
            return 'Ошибка обработки файла, попробуйте позднее';
        }

        return $actionResult;
        /*if(count($actionResult) === 1){
            return 'Ошибка: ' . $actionResult['action_status'];
        }
        if(count($actionResult) === 2){
            // добавлю фон заключению
            $conclusionFile = $actionResult['filename'];
            $path = Yii::getAlias('@conclusionsDirectory') . '\\' . $conclusionFile;
            if(is_file($path)){
                self::addBackgroundToPDF($path);
                // если создан новый файл- зарегистрирую его доступность
                if($actionResult['action_status'] === 'Добавлено дополнительное заключение' || $actionResult['action_status'] === 'Заключение добавлено'){
                    $user = User::findByUsername(GrammarHandler::getBaseFileName($conclusionFile));
                    if($user === null){
                        // создам учётную запись
                        ExecutionHandler::createUser(GrammarHandler::getBaseFileName($conclusionFile));
                    }
                    $user = User::findByUsername(GrammarHandler::getBaseFileName($conclusionFile));
                    $md5 = md5_file($path);
                    (new Table_availability(['file_name' => $conclusionFile, 'is_conclusion' => true, 'md5' => $md5, 'file_create_time' => time(), 'userId' => $user->username]))->save();
                }
                return $actionResult['action_status'];
            }
        }*/
        return 'Не удалось обработать файл';
    }
}