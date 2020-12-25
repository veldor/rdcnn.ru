let checkInterval;
let copyPassTextarea;
let unhandledFoldersContainer;
let unhandledFoldersList;
let waitingFoldersContainer;
let waitingFoldersList;
let patientsCount;
let withoutExecutions;
let withoutConclusions;

function handleLoader(element) {

    element.on('change.sendData', function () {
        if ($(this).val()) {
            let form = element.parents('form');
            // отправлю файл на сервер
            if ($(this).hasClass('addConclusion')) {
                sendAjaxWithFile('/administrator/add-conclusion', simpleAnswerHandler, form);
            } else if ($(this).hasClass('addExecution')) {
                sendAjaxWithFile('/administrator/add-execution-data', simpleAnswerHandler, form);
            }
        }
    });
}

function handlePrint(element) {
    let printer = $(element).find('.printer');
    printer.off('click.print');
    printer.on('click.print', function (e) {
        e.preventDefault();
        let addresses = $(this).attr('data-names');
        let names = addresses.split(" ");
        console.log(names)
        if (names.length > 0) {
            let counter = 0;
            while (names[counter]) {
                console.log(names[counter])
                // Открою новое окно и в нём загружу на печать файл
                let url = '/auto-print/' + names[counter];
                window.open(url);
                counter++;
            }
        }
    });
}

function copyPass() {
    let pass = $(this).attr('data-password');
    copyPassTextarea.removeClass('hidden');
    copyPassTextarea.text(pass);
    copyPassTextarea.select();
    document.execCommand('copy');
    copyPassTextarea.addClass('hidden');
    $(this).html('<span class="text-info">Пароль скопирован</span>');
}

function handleActivator(element) {
    element.tooltip();
    element.on('click.doAction', function () {
        let action = element.attr('data-action');
        let id = element.attr('data-id');
        let attributes = {
            'AdministratorActions[executionId]': id,
        };

        switch (action) {
            case 'change-password':
                makeInformerModal('Смена пароля пользователя', 'Изменить пароль пользователя? Предыдущий пароль перестанет действовать, новый пароль нужно будет каким-то образом сообщить пользователю.', function () {
                    sendAjax('post', '/administrator/change-password', function (data) {
                        let message = data['message'] ? data['message'] : 'Операция успешно завершена';
                        let modal = makeInformerModal("Успешно", message, function () {
                        });
                        let copyPassBtn = modal.find('button#copyPassBtn');
                        modal.on('shown.bs.modal', function () {
                            copyPassBtn.focus();
                        });
                        copyPassBtn.on('click.copy', function () {
                            copyPass.call(this);
                            $('button#acceptActionBtn').focus();
                        });
                    }, attributes);
                }, function () {
                });
                break;
            case 'delete':
                makeInformerModal('Смена пароля пользователя', 'Удалить учётную запись пользователя?', function () {
                    sendAjax('post', '/administrator/delete-item', simpleAnswerHandlerReload, attributes);
                }, function () {
                });
                break;
            case 'check-data':
                sendAjax('get', '/check/files/' + id, simpleAnswerHandler);
                break;
        }
    });
}


function sendFiles(location, files, totalLength) {
    function msToTime(s) {
        let ms = s % 1000;
        s = (s - ms) / 1000;
        let secs = s % 60;
        s = (s - secs) / 60;
        let minutes = s % 60;
        let hrs = (s - minutes) / 60;

        return hrs + 'ч ' + minutes + 'м ' + secs + 'с';
    }

    let startTime = new Date().getTime();
    let totalLoaded = 0;
    let lastFileSize = 0;
    showWaiter();
    dangerReload();
    let shaderStatus = $('div.shader-status');
    shaderStatus.html("Отправка файла 1 из " + files.length + "<br/> Завершено 0%<br/> Не перезагружайте страницу до завершения процесса");
    let counter = 0;
    let file = files[counter];
    lastFileSize = file.size;
    ++counter;
    // буду по очереди отправлять файлы
    let xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', uploadProgress, false);
    xhr.onreadystatechange = stateChange;
    xhr.open('POST', location);
    var fd = new FormData
    fd.append("file", file)
    xhr.send(fd)

    function uploadProgress(event) {
        let percent = parseInt((parseInt(event.loaded) + totalLoaded) / totalLength * 100);
        // подсчитаю примерное оставшееся время для загрузки
        // определю, за какое время загружается 1%
        let now = new Date().getTime();
        let timeDifference = now - startTime;
        let perPercent = timeDifference / percent;
        let spentPercents = 100 - percent;
        let spendMillis = spentPercents * perPercent;
        let time = msToTime(parseInt(spendMillis));
        shaderStatus.html("Отправка файла " + counter + " из " + files.length + "<br/> Завершено " + percent + "%<br/>Приблизительное время до завершения " + time + "<br/>Не перезагружайте страницу до завершения процесса");
    }

    function stateChange(event) {
        if (event.target.readyState === 4) {
            if (event.target.status === 200) {
                console.log(event);
                // проверю, если загружены все файлы- покажу уведомление об успешной загрузке, иначе- гружу следующий файл
                if (counter === files.length) {
                    deleteWaiter();
                    normalReload();
                    makeInformer(
                        "success",
                        "Добавление файлов",
                        "Все файлы загружены!"
                    );
                } else {
                    totalLoaded += lastFileSize;
                    // гружу следующий файл
                    file = files[counter];
                    lastFileSize = file.size;
                    ++counter;
                    let xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', uploadProgress, false);
                    xhr.onreadystatechange = stateChange;
                    xhr.open('POST', location);
                    let fd = new FormData
                    fd.append("file", file)
                    xhr.send(fd)
                }
            } else {
                // ошибка загрузки
                deleteWaiter();
                normalReload();
                makeInformer(
                    "danger",
                    "Добавление файлов",
                    "Не удалось загрузить файл, попробуйте позднее"
                );
            }
        }
    }
}

function handleDragDrop() {
    $("html").on("dragover", function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).addClass('dragging');
    }).on("dragleave", function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).removeClass('dragging');
    });
    let dragContainer = $('div#dragContainer');
    let dragContainerDropArea = $('div#dragContainerDropArea');
    dragContainerDropArea
        .on('dragleave', function () {
            $('div#mainWrap').removeClass("blured");
            dragContainer.hide();
            return false;
        })
        .on('drop', function (e) {
            $('div#mainWrap').removeClass("blured");
            dragContainer.hide();
            e.preventDefault();
            if (e.originalEvent.dataTransfer.files.length > 0) {
                let totalFilesLength = 0;
                // проверю файлы
                for (let counter = 0; counter < e.originalEvent.dataTransfer.files.length; counter++) {
                    let file = e.originalEvent.dataTransfer.files[counter];
                    // проверю, файл должен быть расширения .pdf, .zip, .doc или .docx
                    let extensionArray = file.name.split('.');
                    let extension = extensionArray[extensionArray.length - 1];
                    if (extension !== "pdf" && extension !== "zip" && extension !== "doc" && extension !== "docx") {
                        makeInformer(
                            "danger",
                            "Добавление файлов",
                            "К отправке принимаются только файлы .doc .docx .pdf и .zip"
                        );
                        return;
                    } else {
                        totalFilesLength += file.size;
                    }

                }
                // начну отправку файлов
                sendFiles('/drop', e.originalEvent.dataTransfer.files, totalFilesLength);

            } else {
                console.log("empty");
            }
        });
    $('body')
        .on('dragenter', function () {
            $('div#mainWrap').addClass("blured");
            console.log("show container");
            dragContainer.show();
            return false;
        });
}

$(function () {
    copyPassTextarea = $('textarea#forPasswordCopy');
    unhandledFoldersContainer = $('div#unhandledFoldersContainer');
    unhandledFoldersList = $('tbody#unhandledFoldersList');
    waitingFoldersContainer = $('div#waitingFoldersContainer');
    waitingFoldersList = $('ul#waitingFoldersList');
    patientsCount = $('span#patientsCount');
    withoutConclusions = $('span#withoutConclusions');
    withoutExecutions = $('span#withoutExecutions');

    // обработаю добавление обследования
    let addBtn = $('#addExecution');
    addBtn.on('click.add', function () {
        sendAjax('get', '/execution/add', simpleModalHandler);
    });
    // обработаю добавление адреса электронной почты
    let addMailBtn = $('.add-mail');
    addMailBtn.on('click.add', function () {
        sendAjax('get', $(this).attr('data-action'), simpleModalHandler);
    });


    let activators = $('.custom-activator');
    // назначу каждому из активаторов функцию
    activators.each(function () {
        handleActivator($(this));
    });

    let loaders = $('.loader');
    loaders.each(function () {
        handleLoader($(this));
    });
    checkPatientDataFilling();
    // запущу проверку наличия пациентов
    checkInterval = setInterval(function () {
        checkPatientDataFilling();
    }, 10000);

// чищу мусор
    let clearGarbageBtn = $('button#clearGarbageButton');
    clearGarbageBtn.on('click.clear', function () {
        sendAjax('post', '/clear-garbage', simpleAnswerHandler);
    });

    handleForm();

    handleDragDrop();

    enableTooltips();

    let patients = $('tr.patient');
    patients.each(function (){
        handlePrint(this);
    });
});

function checkPatientDataFilling() {
    sendSilentAjax('get', '/patients/check', function (answer) {
        for (let i in answer) {
            if (i === "patientList") {
                // найден список пациентов
                if (answer.hasOwnProperty(i) && answer[i].length > 0) {
                    // найдены пациенты. Проверю, если до этого пациентов не было,
                    // уберу надпись, что обследования не зарегистрированы
                    let noExecutionsRegisteredDiv = $('div#noExecutionsRegistered');
                    if (noExecutionsRegisteredDiv.length === 1) {
                        noExecutionsRegisteredDiv.remove();
                        // добавлю таблицу
                        let table = "<table class='table-hover table'><thead><tr><th>Номер обследования</th><th>Действия</th><th>Загружено заключение</th><th>Загружены файлы</th></tr></thead><tbody id='executionsBody'></tbody></table>";
                        $('div#mainWrap>div.container').append(table);
                    }
                    // отображу счётчики
                    patientsCount.text(answer[i].length);
                    let withoutExecutionsCounter = 0;
                    let withoutConclusionsCounter = 0;
                    let item;
                    for (let counter = 0; counter < answer[i].length; counter++) {
                        item = answer[i][counter];
                        let user = $('tr[data-id="' + item['id'] + '"]');
                        if (!user.length) {
                            // добавлю новый элемент наверх списка
                            let td = '<tr class="new-element patient" data-id="' + item['id'] + '">';

                            // если определено имя пациента- покажу его при наведении на номер обследования
                            if (item['patient_name']) {
                                td += '<td><a class="btn-link execution-id tooltip-enabled" href="/person/' + item['id'] + '"  data-toggle="tooltip" data-placement="auto" title="' + item['patient_name'] + '">' + item['id'] + '</a>';
                            } else {
                                td += '<td><a class="btn-link execution-id" href="/person/' + item['id'] + '">' + item['id'] + '</a>';
                            }

                            // проверю наличие почты
                            if (item['hasMail']) {
                                let hint = item['mailed'] ? 'Отправить письмо(уже отправлялось)' : 'Отправить письмо';
                                td += "<td class='mail-td'><button class='btn btn-default tooltip-enabled activator' data-action='/send-info-mail/" + item['real_id'] + "' data-toggle='tooltip' data-placement='auto' title='" + hint + "'><span class='glyphicon glyphicon-circle-arrow-right text-info'></span></button><button class='btn btn-default add-mail tooltip-enabled' data-action='/mail/add/" + item['real_id'] + "' data-toggle='tooltip' data-placement='auto' title='Изменить электронную почту'><span class='glyphicon glyphicon-envelope text-info'></span></button></td>";
                            } else {
                                td += "<td class='mail-td'><button class='btn btn-default add-mail tooltip-enabled' data-action='/mail/add/" + item['real_id'] + "' data-toggle='tooltip' data-placement='auto' title='Добавить электронную почту'><span class='glyphicon glyphicon-envelope text-success'></span></button></td>";
                            }

                            // проверю наличие заключения
                            if (item['conclusionsCount'] > 0) {
                                if (item['conclusion_areas']) {
                                    let areasText = '';
                                    for (let i = 0; item['conclusion_areas'][i]; i++) {
                                        areasText += item['conclusion_areas'][i] + " \n";
                                    }
                                    td += "<td data-conclusion='" + item['id'] + "' class='field-success'><span class='glyphicon glyphicon-ok text-success status-icon' data-toggle='tooltip' data-placement='auto' title='" + areasText + "'></span><b>(" + item['conclusionsCount'] + ")</b></td>";
                                } else {
                                    td += "<td data-conclusion='" + item['id'] + "' class='field-success'><span class='glyphicon glyphicon-ok text-success status-icon'></span><b>(" + item['conclusionsCount'] + ")</b></td>";
                                }

                            } else {
                                td += "<td data-conclusion='" + item['id'] + "' class='field-danger'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>"
                            }
                            // проверю наличие снимков
                            if (item['execution']) {
                                td += "<td data-execution='" + item['id'] + "' class='field-success'><span class='glyphicon glyphicon-ok text-success status-icon'></span></td>";
                            } else {
                                td += "<td data-execution='" + item['id'] + "' class='field-danger'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>"
                            }
                            td += "<td><a class=\"btn btn-default custom-activator\" data-action=\"change-password\" data-id=\"" + item['id'] + "\" data-toggle=\"tooltip\" data-placement=\"auto\" title=\"\" data-original-title=\"Сменить пароль\"><span class=\"text-info glyphicon glyphicon-retweet\"></span></a><a class=\"btn btn-default custom-activator\" data-action=\"delete\" data-id=\"" + item['id'] + "\" data-toggle=\"tooltip\" data-placement=\"auto\" title=\"\" data-original-title=\"Удалить запись\"><span class=\"text-danger glyphicon glyphicon-trash\"></span></a></td>"
                            td += '</tr>';
                            td = $(td);
                            let addMailBtn = td.find('.add-mail');
                            addMailBtn.on('click.add', function () {
                                sendAjax('get', $(this).attr('data-action'), simpleModalHandler);
                            });
                            // активирую функции
                            let activators = td.find('.custom-activator');
                            // назначу каждому из активаторов функцию
                            activators.each(function () {
                                handleActivator($(this));
                            });

                            td.find('.tooltip-enabled').tooltip();

                            let loaders = td.find('.loader');
                            loaders.each(function () {
                                handleLoader($(this));
                            });

                            $('tbody#executionsBody').prepend(td);
                        } else {
                            // запись найдена, проверю актуальность данных
                            // проверю наличие фиo пациента
                            if (item['patient_name']) {
                                // изменю текст первой ячейки
                                user.find('a.execution-id').attr('data-toggle', 'tooltip').attr('data-placement', 'auto').attr('data-original-title', item['patient_name']).tooltip();
                            }
                            if (item['hasMail']) {
                                let hint = item['mailed'] ? 'Отправить письмо<br/>(уже отправлялось)' : 'Отправить письмо';
                                let color = item['mailed'] ? 'text-danger' : 'text-info';
                                user.find('td.mail-td').html("<button class='btn btn-default tooltip-enabled activator' data-action='/send-info-mail/" + item['real_id'] + "' data-toggle='tooltip' data-html='true' data-placement='auto' title='" + hint + "'><span class='glyphicon glyphicon-circle-arrow-right " + color + "'></span></button><button class='btn btn-default add-mail tooltip-enabled' data-action='/mail/add/" + item['real_id'] + "' data-toggle='tooltip' data-placement='auto' title='Изменить электронную почту'><span class='glyphicon glyphicon-envelope text-info'></span></button>");
                            } else {
                                user.find('td.mail-td').html("<button class='btn btn-default add-mail tooltip-enabled' data-action='/mail/add/" + item['real_id'] + "' data-toggle='tooltip' data-placement='auto' title='Добавить электронную почту'><span class='glyphicon glyphicon-envelope text-success'></span></button>")
                            }
                            let addMailBtn = user.find('.add-mail');
                            addMailBtn.on('click.add', function () {
                                sendAjax('get', $(this).attr('data-action'), simpleModalHandler);
                            });
                            user.find('.tooltip-enabled').tooltip();

                            let conclusionContainer = $('td[data-conclusion="' + item['id'] + '"]');
                            if (conclusionContainer.length) {
                                if (item['conclusionsCount'] > 0) {
                                    conclusionContainer.addClass('field-success').removeClass('field-danger');
                                    let cText = '';
                                    if(item['conclusion_text']){
                                        cText = item['conclusion_text'];
                                    }
                                    if (item['conclusion_areas']) {
                                        let areasText = '';
                                        for (let i = 0; item['conclusion_areas'][i]; i++) {
                                            areasText += item['conclusion_areas'][i] + " <br/>\n";
                                        }
                                        conclusionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon tooltip-enabled' data-html='true' data-toggle='tooltip' data-placement='auto' title='" + areasText + "'></span><b>(" + item['conclusionsCount'] + ")</b><button class='btn btn-default activator tooltip-enabled' data-action='/delete/conclusions/" + item['id'] + "' data-toggle='tooltip' data-placement='auto' title='Удалить все заключения по обследованию'><span class='glyphicon glyphicon-trash text-danger'></span></button><button class='btn btn-default tooltip-enabled printer'  data-toggle='tooltip' data-placement='auto' title='Распечатать копию' data-names='" + cText + "'><span class='text-info glyphicon glyphicon-print'></span></button>");
                                    } else {
                                        conclusionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span><b>(" + item['conclusionsCount'] + ")</b><button class='btn btn-default activator tooltip-enabled' data-action='/delete/conclusions/" + item['id'] + "' data-toggle='tooltip' data-placement='auto' title='Удалить все заключения по обследованию'><span class='glyphicon glyphicon-trash text-danger'></span></button><button class='btn btn-default tooltip-enabled printer'  data-toggle='tooltip' data-placement='auto' title='Распечатать копию' data-names='\" + cText + \"'><span class='text-info glyphicon glyphicon-print'></span></button>");
                                    }
                                    handlePrint(conclusionContainer);
                                } else {
                                    conclusionContainer.removeClass('field-success').addClass('field-danger');
                                    conclusionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                                }
                            }
                            let executionContainer = $('td[data-execution="' + item['id'] + '"]');
                            if (executionContainer.length) {
                                if (item['execution']) {
                                    executionContainer.addClass('field-success').removeClass('field-danger');
                                    executionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span>").removeClass('field-danger').addClass('field-success');
                                } else {
                                    executionContainer.removeClass('field-success').addClass('field-danger');
                                    executionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                                }
                            }
                        }
                        if (!item['execution']) {
                            ++withoutExecutionsCounter;
                        }
                        if (item['conclusionsCount'] === 0) {
                            ++withoutConclusionsCounter;
                        }
                    }
                    handleAjaxActivators();
                    enableTooltips();
                    withoutConclusions.text(withoutConclusionsCounter);
                    withoutExecutions.text(withoutExecutionsCounter);
                    // теперь нужно убрать удалённые обследования
                    let existent = $('tr.patient');
                    // если число существующих обследований не равно числу подгруженных- удаляю несуществующие
                    if (existent.length !== answer[i].length) {
                        existent.each(function () {
                            // тут большой цикл- возьму id обследования. Если его нет в списке подгруженных- удалю его из очереди
                            let id = $(this).attr('data-id');
                            let found = false;
                            for (let loadedPatientsCounter = 0; loadedPatientsCounter < answer[i].length; loadedPatientsCounter++) {
                                if (id === answer[i][loadedPatientsCounter]['id']) {
                                    // элемент найден
                                    found = true;
                                    break;
                                }
                            }
                            if (!found) {
                                $(this).remove();
                            }
                        })
                    }
                }
            }
        }
    });
}

function handleForm() {
    let form = $('form#addPatientForm');
    let idInput = $('#executionhandler-executionnumber');
    let pasteFromClipboard = $('#pasteFromClipboard');
    pasteFromClipboard.on('click', function () {
        idInput.focus();
        idInput.select();
        setTimeout(function () {
            document.execCommand("Paste", null, null);
        }, 500);
        document.execCommand('Paste');
    });

    form.on('submit', function (e) {
        e.preventDefault();
        if (idInput.val()) {
            sendAjaxWithFile(form.attr('action'), function (data) {
                let message = data['message'] ? data['message'] : 'Операция успешно завершена';
                let modal = makeInformerModal("Успешно", message);
                let copyPassBtn = modal.find('button#copyPassBtn');
                modal.on('shown.bs.modal', function () {
                    copyPassBtn.focus();
                });
                copyPassBtn.on('click.copy', function () {
                    copyPass.call(this);
                    $('button#acceptActionBtn').focus();
                });
            }, form);
        }
    });
}