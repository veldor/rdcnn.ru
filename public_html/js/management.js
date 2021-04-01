"use strict";

let sendingCouner = 0;
let sendInProgress = false;
let waitingMessages;
let skipMessagesCheck = false;
let unsendedMessagesCounter;
let beginSendingBtn;

function recursiveSendMessages(waitingMessages, sendingCouner) {
    if (sendInProgress) {
        let targetMessage = waitingMessages.eq(sendingCouner);
        targetMessage.removeClass('text-info').addClass('text-primary').text('Отправка');
        sendSilentAjax('post',
            '/send-message',
            function (data) {
                if (data.status && data.status === 1) {
                    ++sendingCouner;
                    makeInformer('success',
                        'Успешная отправка',
                        'Сообщение отправлено и удалено из очереди отправки',
                        true);
                    unsendedMessagesCounter.text(waitingMessages.length - sendingCouner);
                    targetMessage.removeClass('text-primary').addClass('text-success').text('Отправлено');
                    targetMessage.parents('tr').eq(0).remove();
                    if (sendingCouner < waitingMessages.length) {
                        recursiveSendMessages(waitingMessages, sendingCouner);
                    } else {
                        location.reload();
                    }
                } else if (data.message) {
                    skipMessagesCheck = false;
                    // возникла ошибка отправки
                    makeInformer('danger', 'ошибка отправки', data.message);
                    targetMessage.removeClass('text-primary').addClass('text-danger').text('Не отправлено');
                    sendInProgress = !sendInProgress;
                    beginSendingBtn.find('span').text('Продолжить рассылку').removeClass('text-danger').addClass('text-info');
                }
            },
            {'id': targetMessage.attr('data-schedule-id')},
            false,
            true);
    }
}

function handleMailing(){
    unsendedMessagesCounter = $('span#unsendedMessagesCounter');
    beginSendingBtn = $('button#beginSendingBtn');
    waitingMessages = $('b.mailing-status');
    beginSendingBtn.on('click.beginSending', function () {
        skipMessagesCheck = true;
        sendInProgress = !sendInProgress;
        if (!sendInProgress) {
            makeInformer('success', 'Успешно', 'Отправка остановлена');
            waitingMessages = $('b.mailing-status');
            $(this).find('span').text('Продолжить рассылку').removeClass('text-danger').addClass('text-info');
        }
        else{
            $(this).find('span').text('Остановить отправку').removeClass('text-success').addClass('text-danger');
            if (waitingMessages) {
                // рекурсивно отправлю все сообщения
                recursiveSendMessages(waitingMessages, sendingCouner);
            } else {
                makeInformer('success', 'Завершено', 'Нет неотправленных сообщений');
                $(this).prop('disabled', false).text('Начать рассылку');
            }
        }
    });

}
$(function () {
    enableTabNavigation();
    handleAjaxActivators();
    handleMailing();
});