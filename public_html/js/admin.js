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
});