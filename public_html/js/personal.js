let checkInterval;
let addConc;
let isAddConc = false;
$(function () {
        // назначу переменные для кнопок
    let downloadConclusionBtn = $('#downloadConclusionBtn');
    let printConclusionBtn = $('#printConclusionBtn');
    let conclusionNotReadyBtn = $('#conclusionNotReadyBtn');

    let downloadExecutionBtn = $('#downloadExecutionBtn');
    let executionNotReadyBtn = $('#executionNotReadyBtn');


    let availabilityTimeDivContainer = $('#availabilityTimeContainer');
    let availabilityTimeContainer = $('#availabilityTime');
    let removeReasonContainer = $('#removeReasonContainer');

    let clearDataBtn = $('#clearDataBtn');
    clearDataBtn.on('click.clear', function () {
        makeInformerModal('Удалить данные?', 'Убрать все данные с сервера, чтобы никто не мог получить к ним доступ. Если вы удалите данные, то получить повторный доступ к ним вы сможете, обратившись в наш центр.', function () {
                clearInterval(checkInterval);
                sendAjax('post', '/user/delete-execution', function () {
                    makeInformerModal('Успешно', 'Все ваши данные удалены. Получить повторный доступ к ним вы сможете, обратившись в наш центр.');
                });
            }
            , function () {
            });
    });

    function checkAvailability(){
        sendSilentAjax('get', '/availability/check', handleAvailability);
    }

    function handleAvailability(data) {
        // если запрос успешен
        if(data['status'] === 1){
            // проверю, если есть заключение- активирую пункты о скачивании
            if(data['execution']){
                downloadExecutionBtn.removeClass('hidden');
                executionNotReadyBtn.addClass('hidden');
            }
            if(data['conclusion']){
                downloadConclusionBtn.removeClass('hidden');
                printConclusionBtn.removeClass('hidden');
                conclusionNotReadyBtn.addClass('hidden');
                removeReasonContainer.removeClass('hidden');
            }
            if(data['timeLeft']){
                availabilityTimeDivContainer.removeClass('hidden');
                availabilityTimeContainer.html(data['timeLeft'])
            }
            if(data['addConc']){
                // проверю, есть ли уже ссылки на скачивание дополнительных заключений
                if(isAddConc && addConc !== data['addConc']){
                    location.reload();
                }
                else{
                    addConc = data['addConc'];
                    isAddConc = true;
                }
            }
            // проверю, если есть файлы обследования- активирую пункты о скачивании
        }
        else if(data['status'] === 2){
            clearInterval(checkInterval);
            makeInformerModal('Результаты заключения не найдены', 'Возможно, они были удалены вами или истёк срок хранения. Для повторного получения результатов вы можете позвонить нам!');
        }
    }
    checkAvailability();
    checkInterval = setInterval(function () {
        checkAvailability();
    }, 60000);
});

