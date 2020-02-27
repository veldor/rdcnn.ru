let checkInterval;
$(function () {
    let copyPassTextarea = $('textarea#forPasswordCopy');

    // обработаю добавление обследования
    let addBtn = $('#addExecution');
    addBtn.on('click.add', function () {
        sendAjax('get', '/execution/add', simpleModalHandler);
    });
    let activators = $('.activator');
    activators.tooltip();
    activators.on('click.doAction', function () {
        let action = $(this).attr('data-action');
        let id = $(this).attr('data-id');
        let attributes = {
            'AdministratorActions[executionId]': id,
        };

        switch (action) {
            case 'change-password':
                makeInformerModal('Смена пароля пользователя', 'Изменить пароль пользователя? Предыдущий пароль перестанет действовать, новый пароль нужно будет каким-то образом сообщить пользователю.', function () {
                    sendAjax('post', '/administrator/change-password', function (data) {
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
                    }, attributes);
                }, function () {});
                break;
            case 'delete':
                makeInformerModal('Смена пароля пользователя', 'Удалить учётную запись пользователя?', function () {
                    sendAjax('post', '/administrator/delete-item', simpleAnswerHandler, attributes);
                }, function () {});
                break;
            case 'check-data':
                sendAjax('get', '/check/files/' + id, simpleAnswerHandler);
                break;
        }
    });


    let loaders = $('.loader');
    loaders.on('change.sendData', function () {
        if ($(this).val()) {
            let form = $(this).parents('form');
            // отправлю файл на сервер
            switch ($(this).attr('id')) {
                case 'addConclusion':
                    sendAjaxWithFile('/administrator/add-conclusion', simpleAnswerHandler, form);
                    break;
                case 'addExecution':
                    sendAjaxWithFile('/administrator/add-execution-data', simpleAnswerHandler, form);
                    break;
            }
        }
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
        for (let i in answer){
            if(answer.hasOwnProperty(i)){
                let item = answer[i];
                let conclusionContainer = $('td[data-conclusion="' + item['id'] +'"]');
                if(conclusionContainer.length){
                    if(item['conclusion']){
                        conclusionContainer.html("<span class='glyphicon glyphicon-ok text-success'></span>");
                    }
                    else{
                        conclusionContainer.html("<span class='glyphicon glyphicon-remove text-danger'></span>");
                    }
                }
                let executionContainer = $('td[data-execution="' + item['id'] +'"]');
                if(executionContainer.length){
                    if(item['execution']){
                        executionContainer.html("<span class='glyphicon glyphicon-ok text-success'></span>");
                    }
                    else{
                        executionContainer.html("<span class='glyphicon glyphicon-remove text-danger'></span>");
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