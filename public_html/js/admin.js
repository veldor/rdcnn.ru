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
        var ms = s % 1000;
        s = (s - ms) / 1000;
        var secs = s % 60;
        s = (s - secs) / 60;
        var mins = s % 60;
        var hrs = (s - mins) / 60;

        return hrs + 'ч ' + mins + 'м ' + secs + 'с';
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
        let percent = parseInt((event.loaded + totalLoaded) / totalLength * 100);
        // подсчитаю примерное оставшееся время для загрузки
        // определю, за какое время загружается 1%
        let now = new Date().getTime();
        let timeDifference = now - startTime;
        let perPercent = timeDifference / percent;
        let spendedPercents = 100 - percent;
        let spendMillis = spendedPercents * perPercent;
        let time = msToTime(parseInt(spendMillis));
        shaderStatus.html("Отправка файла " + counter + " из " + files.length + "<br/> Завершено " + percent + "%<br/>Приблизительное время до завершения " + time + "<br/>Не перезагружайте страницу до завершения процесса");
    }

    function stateChange(event) {
        if (event.target.readyState == 4) {
            if (event.target.status == 200) {
                console.log(event);
                // проверю, если загружены все файлы- покажу уведомление об успешной загрузке, иначе- гружу следующий файл
                if(counter === files.length){
                    deleteWaiter();
                    normalReload();
                    makeInformer(
                        "success",
                        "Добавление файлов",
                        "Все файлы загружены!"
                    );
                }
                else{
                    totalLoaded += lastFileSize;
                    // гружу следующий файл
                    file = files[counter];
                    lastFileSize = file.size;
                    ++counter;
                    let xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', uploadProgress, false);
                    xhr.onreadystatechange = stateChange;
                    xhr.open('POST', location);
                    var fd = new FormData
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


    let activators = $('.activator');
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
});

function checkPatientDataFilling() {
    sendSilentAjax('get', '/patients/check', function (answer) {
        for (let i in answer) {
            if (i === "waitingFolders") {
                if (answer.hasOwnProperty(i) && answer[i].length > 0) {
                    waitingFoldersContainer.removeClass('hidden');
                    // очищу список
                    waitingFoldersList.html("");
                    for (let counter = 0; counter < answer[i].length; counter++) {
                        waitingFoldersList.append('<li class="text-center"><b class="text-info">' + answer[i][counter] + '</b></li>');
                    }
                }
            }
            if (i === "unhandledFolders") {
                if (answer.hasOwnProperty(i) && answer[i].length > 0) {
                    // найден список неопознанных папок, отображу его
                    unhandledFoldersContainer.removeClass('hidden');
                    // очищу список
                    unhandledFoldersList.html("");
                    let item;
                    let newElement;
                    // отображу список
                    for (let counter = 0; counter < answer[i].length; counter++) {
                        item = answer[i][counter];
                        newElement = $('<tr class="unhandled-folder-list-item" data-name="' + item + '"><td><b class="text-danger">' + item + '</b></td><td><a class="btn btn-default activator change-unhandled-folder" data-toggle="tooltip" data-placement="auto"  data-name="' + item + '" data-title="Изменить имя"><span class="glyphicon glyphicon-pencil text-info"></span></a><a class="btn btn-default activator delete-unhandled-folder" data-toggle="tooltip"  data-name="' + item + '" data-placement="auto" data-title="Удалить папку"><span class="glyphicon glyphicon-trash text-danger"></span></a></td></tr>');
                        newElement.find('.activator').tooltip();
                        // удалю папку
                        newElement.find('.delete-unhandled-folder').on('click.delete', function () {
                            let name = $(this).attr('data-name');
                            // выдам предупреждение об удалении папки
                            makeInformerModal(
                                "Удаление неопознанной папки",
                                "Папка <b class='text-info'>" + name + "</b> будет безвозвратно удалена. Выполнить действие?",
                                function () {
                                    sendAjax('post',
                                        '/delete-unhandled-folder',
                                        function (data) {
                                            if (data.hasOwnProperty('status')) {
                                                unhandledFoldersList.find('tr[data-name="' + name + '"]').remove();
                                                makeInformerModal("Успех",
                                                    "Папка <b class='text-info'>" + name + "</b> Удалена!",
                                                    function () {
                                                    })
                                            }
                                        },
                                        {'folderName': name}
                                    )
                                },
                                function () {
                                }
                            )
                        });

                        newElement.find('.change-unhandled-folder').on('click.editName', function () {
                            let name = $(this).attr('data-name');
                            makeInformerModal("Изменение названия папки",
                                "<input class='form-control' id='changeUnhandledFolderName' value='" + name + "'/>",
                                function () {
                                    let newName = $('#changeUnhandledFolderName').val();
                                    if (newName) {
                                        sendAjax('post',
                                            '/rename-unhandled-folder',
                                            function () {
                                                makeInformerModal("Успех", "Папка <b class='text-info'>" + name + "</b> переименована в <b class='text-success'>" + newName + "</b>");
                                            },
                                            {'oldName': name, 'newName': newName}
                                        )
                                    }
                                },
                                function () {
                                }
                            )
                        });
                        // добавлю элемент
                        unhandledFoldersList.append(newElement);
                    }
                } else {
                    // неопознанных папок не найдено, скрою список
                    unhandledFoldersContainer.addClass('hidden');
                }
            }
            if (i === "patientList") {
                if (answer.hasOwnProperty(i) && answer[i].length > 0) {
                    patientsCount.text(answer[i].length);
                    let withoutExecutionsCounter = 0;
                    let withoutConclusionsCounter = 0;
                    let item;
                    for (let counter = 0; counter < answer[i].length; counter++) {
                        item = answer[i][counter];
                        let user = $('tr[data-id="' + item['id'] + '"]');
                        if (!user.length) {
                            // добавлю новый элемент наверх списка
                            let td = $('<tr class="new-element patient" data-id="' + item['id'] + '">\n' +
                                '            <td>\n' +
                                '                <a class="btn-link execution-id" href="/person/' + item['id'] + '">' + item['id'] + '</a>\n' +
                                '            </td>\n' +
                                '            <td>\n' +
                                '\n' +
                                '                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="' + item['id'] + '"></label><label class="btn btn-default activator" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Добавить заключение"><span class="text-info glyphicon glyphicon-file"></span><input id="addConclusion" data-id="' + item['id'] + '" class="hidden loader" type="file" accept="application/pdf" name="AdministratorActions[conclusion]"></label></form>\n' +
                                '                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="' + item['id'] + '"></label><label class="btn btn-default activator" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Добавить обследование"><span class="text-info glyphicon glyphicon-folder-close"></span><input id="addExecution" data-id="' + item['id'] + '" class="hidden loader" type="file" accept="application/zip" name="AdministratorActions[execution]"></label></form>\n' +
                                '            </td>\n' +
                                '            <td data-conclusion="' + item['id'] + '"><span class="glyphicon glyphicon-remove text-danger"></span></td>\n' +
                                '            <td data-execution="' + item['id'] + '"><span class="glyphicon glyphicon-remove text-danger"></span></td>\n' +
                                '            <td>\n' +
                                '                <a class="btn btn-default activator" data-action="change-password" data-id="' + item['id'] + '" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Сменить пароль"><span class="text-info glyphicon glyphicon-retweet"></span></a>\n' +
                                '                <a class="btn btn-default activator" data-action="delete" data-id="' + item['id'] + '" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Удалить запись"><span class="text-danger glyphicon glyphicon-trash"></span></a>\n' +
                                '            </td>\n' +
                                '        </tr>');

                            // активирую функции
                            let activators = td.find('.activator');
                            // назначу каждому из активаторов функцию
                            activators.each(function () {
                                handleActivator($(this));
                            });

                            let loaders = td.find('.loader');
                            loaders.each(function () {
                                handleLoader($(this));
                            });

                            $('tbody#executionsBody').prepend(td);
                        }
                        let conclusionContainer = $('td[data-conclusion="' + item['id'] + '"]');
                        if (conclusionContainer.length) {
                            if (item['conclusionsCount'] > 0) {
                                conclusionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span> <b>(" + item['conclusionsCount'] + ")</b>").removeClass('field-danger').addClass('field-success');
                            } else {
                                conclusionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                            }
                        }
                        let executionContainer = $('td[data-execution="' + item['id'] + '"]');
                        if (executionContainer.length) {
                            if (item['execution']) {
                                executionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span>").removeClass('field-danger').addClass('field-success');
                            } else {
                                executionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                            }
                        }
                        if (!item['execution']) {
                            ++withoutExecutionsCounter;
                        }
                        if (item['conclusionsCount'] === 0) {
                            ++withoutConclusionsCounter;
                        }
                    }
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