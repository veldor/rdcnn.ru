let checkInterval;
let copyPassTextarea;
let unhandledFoldersContainer;
let unhandledFoldersList;
let patientsCount;
let withoutExecutions;
let withoutConclusions;

function handleLoader(element) {

    element.on('change.sendData', function () {
        if ($(this).val()) {
            let form = element.parents('form');
            // отправлю файл на сервер
            if($(this).hasClass('addConclusion')){
                sendAjaxWithFile('/administrator/add-conclusion', simpleAnswerHandler, form);
            }
            else if($(this).hasClass('addExecution')){
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


$(function () {
    copyPassTextarea = $('textarea#forPasswordCopy');
    unhandledFoldersContainer = $('div#unhandledFoldersContainer');
    unhandledFoldersList = $('tbody#unhandledFoldersList');
    patientsCount = $('span#patientsCount');
    withoutConclusions = $('span#withoutConclusions');
    withoutExecutions = $('span#withoutExecutions');

    // обработаю добавление обследования
    let addBtn = $('#addExecution');
    addBtn.on('click.add', function () {
        sendAjax('get', '/execution/add', simpleModalHandler);
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
});

function checkPatientDataFilling() {
    sendSilentAjax('get', '/patients/check', function (answer) {
        for (let i in answer) {
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
                    if(existent.length !== answer[i].length){
                        existent.each(function () {
                            // тут большой цикл- возьму id обследования. Если его нет в списке подгруженных- удалю его из очереди
                            let id = $(this).attr('data-id');
                            let found = false;
                            for (let loadedPatientsCounter = 0; loadedPatientsCounter < answer[i].length; loadedPatientsCounter++) {
                                if(id === answer[i][loadedPatientsCounter]['id']){
                                    // элемент найден
                                    found = true;
                                    break;
                                }
                            }
                            if(!found){
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