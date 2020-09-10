function handleForm() {
    let modal = $('.modal');
    let form = modal.find('form');
    form.on('submit', function (e) {
        e.preventDefault();
        sendAjax(
            'post',
            '/mail/add',
            function (data){
                if(data.status === 1){
                    modal.modal('hide');
                }
                simpleAnswerHandler(data);
            },
            form,
            true
            );
    });
}

handleForm();