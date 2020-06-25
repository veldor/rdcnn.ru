<?php


namespace app\models;


use DateTime;
use Exception;
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
    public static function getOutputInfo(): string
    {
        $file = Yii::$app->basePath . '\\logs\\file.log';
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
        $file = Yii::$app->basePath . '\\logs\\err.log';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return 'file is empty';
    }

    public static function setUpdateInProgress(): void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, "1");
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
            return (bool)$content;
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
                if (!empty($content) && strlen($content) > 0) {
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

    public static function setLastCheckUpdateTime()
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
                    } catch (\Exception $e) {
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

            /*            // получу сведения о файле
                        $fpdf = new FPDF();
                        $fpdf->AddPage();
                        $fpdf->Image($pdfBackgoundImage, 0, 0, $fpdf->GetPageWidth(), $fpdf->GetPageHeight());
                        $fpdf->Output(Yii::$app->basePath . '\\test1.pdf', 'F');*/
        }
    }

    public static function addBackgrounds(): void
    {
        // получу все файлы в папке с заключениями и добавлю им фон
        $conclusionsDir = Yii::getAlias('@conclusionsDirectory');
        if(is_dir($conclusionsDir)){
            $files = array_slice(scandir($conclusionsDir), 2);
            if(!empty($files && count($files) > 0)){
                foreach ($files as $file) {
                    if(str_ends_with($file, '.pdf')){
                        try{
                            self::addBackgroundToPDF($conclusionsDir . DIRECTORY_SEPARATOR . $file);
                            echo "background added\n";
                        }
                        catch (Exception $e){
                            echo $e->getMessage() . "\n";
                        }
                    }
                }
            }
        }
    }
}