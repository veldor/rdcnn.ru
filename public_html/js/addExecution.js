function handleForm() {
    let copyPassTextarea = $('textarea#forPasswordCopy');
    let modal = $('.modal');
    let form = modal.find('form');
    form.on('submit', function (e) {
        e.preventDefault();
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
    });
}

handleForm();