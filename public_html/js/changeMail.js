function handleForm() {
    let modal = $('.modal');
    let form = modal.find('form');
    form.on('submit', function (e) {
        e.preventDefault();
        sendAjax(
            'post',
            '/mail/change',
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

    let deleteMailButton = modal.find('button#deleteMailBtn');
    deleteMailButton.on('click.delete', function (){
        // получу подтверждение действию
        makeInformerModal(
            'Удаление адреса электронной почты',
            'Точно стираем адрес?',
            function () {
                sendAjax(
                    'post',
                    '/mail/delete',
                    function (data){
                        if(data.status === 1){
                            modal.modal('hide');
                        }
                        simpleAnswerHandler(data);
                    },
                    {'id' : deleteMailButton.attr('data-id')}
                );
            },
            function () {}
        );
    });
}

handleForm();