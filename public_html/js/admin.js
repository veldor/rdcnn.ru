let checkInterval;
let copyPassTextarea;

function handleLoader(element) {

    element.on('change.sendData', function () {
        if ($(this).val()) {
            let form = element.parents('form');
            // отправлю файл на сервер
            switch ($(this).attr('class')) {
                case 'addConclusion':
                    sendAjaxWithFile('/administrator/add-conclusion', simpleAnswerHandler, form);
                    break;
                case 'addExecution':
                    sendAjaxWithFile('/administrator/add-execution-data', simpleAnswerHandler, form);
                    break;
            }
        }
    });
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
                        let modal = makeInformerModal("Успешно", message, function () {});
                        let copyPassBtn = modal.find('button#copyPassBtn');
                        copyPassBtn.on('click.copy', function () {
                            let pass = $(this).attr('data-password');
                            copyPassTextarea.removeClass('hidden');
                            copyPassTextarea.text(pass);
                            copyPassTextarea.select();
                            document.execCommand('copy');
                            copyPassTextarea.addClass('hidden');
                            $(this).html('<span class="text-info">Пароль скопирован</span>');
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
        for (let i in answer){
            if(answer.hasOwnProperty(i)){
                let item = answer[i];
                // проверю, существует ли строка с данным обследованием
                let user = $('tr[data-id="' +  item['id'] + '"]');
                if(!user.length){
                    // добавлю новый элемент наверх списка
                    let td = $('<tr class="new-element" data-id="' + item['id'] +'">\n' +
                        '            <td>\n' +
                        '                <a class="btn-link execution-id" href="/person/' + item['id'] +'">' + item['id'] +'</a>\n' +
                        '            </td>\n' +
                        '            <td>\n' +
                        '\n' +
                        '                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="' + item['id'] +'"></label><label class="btn btn-default activator" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Добавить заключение"><span class="text-info glyphicon glyphicon-file"></span><input id="addConclusion" data-id="' + item['id'] +'" class="hidden loader" type="file" accept="application/pdf" name="AdministratorActions[conclusion]"></label></form>\n' +
                        '                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="' + item['id'] +'"></label><label class="btn btn-default activator" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Добавить обследование"><span class="text-info glyphicon glyphicon-folder-close"></span><input id="addExecution" data-id="' + item['id'] +'" class="hidden loader" type="file" accept="application/zip" name="AdministratorActions[execution]"></label></form>\n' +
                        '            </td>\n' +
                        '            <td data-conclusion="' + item['id'] +'"><span class="glyphicon glyphicon-remove text-danger"></span></td>\n' +
                        '            <td data-execution="' + item['id'] +'"><span class="glyphicon glyphicon-remove text-danger"></span></td>\n' +
                        '            <td>\n' +
                        '                <a class="btn btn-default activator" data-action="change-password" data-id="' + item['id'] +'" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Сменить пароль"><span class="text-info glyphicon glyphicon-retweet"></span></a>\n' +
                        '                <a class="btn btn-default activator" data-action="delete" data-id="' + item['id'] +'" data-toggle="tooltip" data-placement="auto" title="" data-original-title="Удалить запись"><span class="text-danger glyphicon glyphicon-trash"></span></a>\n' +
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
                    let conclusionContainer = $('td[data-conclusion="' + item['id'] +'"]');
                    if(conclusionContainer.length){
                        if(item['conclusion']){
                            conclusionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span>").removeClass('field-danger').addClass('field-success');
                        }
                        else{
                            conclusionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                        }
                    }
                    let executionContainer = $('td[data-execution="' + item['id'] +'"]');
                    if(executionContainer.length){
                        if(item['execution']){
                            executionContainer.html("<span class='glyphicon glyphicon-ok text-success status-icon'></span>").removeClass('field-danger').addClass('field-success');
                        }
                        else{
                            executionContainer.html("<span class='glyphicon glyphicon-remove text-danger status-icon'></span>").addClass('field-danger').removeClass('field-success');
                        }
                    }
            }
        }
    });
}

function handleForm() {
    let copyPassTextarea = $('textarea#forPasswordCopy');
    let form = $('form#addPatientForm');
    let idInput = $('#executionhandler-executionnumber');

    let pasteFromClipboard = $('#pasteFromClipboard');
    pasteFromClipboard.on('click', function () {
        idInput.focus();
        idInput.select();
        setTimeout(function() {console.log('paste'); document.execCommand("Paste", null, null);}, 500);
        document.execCommand('Paste');
    });

    form.on('submit', function (e) {
        e.preventDefault();
        if(idInput.val()){
            sendAjaxWithFile(form.attr('action'), function (data) {
                let message = data['message'] ? data['message'] : 'Операция успешно завершена';
                let modal = makeInformerModal("Успешно", message);
                let copyPassBtn = modal.find('button#copyPassBtn');
                copyPassBtn.on('click.copy', function () {
                    let pass = $(this).attr('data-password');
                    copyPassTextarea.removeClass('hidden');
                    copyPassTextarea.text(pass);
                    copyPassTextarea.select();
                    document.execCommand('copy');
                    copyPassTextarea.addClass('hidden');
                    $(this).html('<span class="text-info">Пароль скопирован</span>');
                });
            }, form);
        }
    });
}