function handleForm() {
    let copyPassTextarea = $('textarea#forPasswordCopy');
    let modal = $('.modal');
    let form = modal.find('form');
    let idInput = $('#executionhandler-executionnumber');
    setTimeout(function() {idInput.focus();}, 500);

    let pasteFromClipboard = $('#pasteFromClipboard');
    pasteFromClipboard.on('click', function () {
        idInput.focus();
        idInput.select();
        setTimeout(function() {console.log('paste'); document.execCommand("Paste", null, null);}, 500);
        document.execCommand('Paste');
    });
    form.on('submit', function (e) {
        e.preventDefault();
        sendAjaxWithFile(form.attr('action'), function (data) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            let modal = makeInformerModal("Успешно", message);
            let copyPassBtn = modal.find('button#copyPassBtn');
            copyPassBtn.on('click.copy', function () {
                copyPass.call(this);
            });
        }, form);
    });
}

handleForm();